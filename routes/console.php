<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pos:backup {--path= : Directory where backup files should be written} {--keep=7 : Number of backup generations to retain}', function () {
    $connection = DB::connection();
    $driver = $connection->getDriverName();
    $backupDir = $this->option('path') ?: storage_path('app/backups');
    $backupDir = rtrim((string) $backupDir, DIRECTORY_SEPARATOR);
    $timestamp = now()->format('Ymd-His');
    $archivePath = $backupDir.DIRECTORY_SEPARATOR."pos-backup-{$timestamp}.jsonl.gz";
    $manifestPath = $backupDir.DIRECTORY_SEPARATOR."pos-backup-{$timestamp}.manifest.json";

    File::ensureDirectoryExists($backupDir);

    $tables = match ($driver) {
        'sqlite' => collect(DB::select(
            "select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name"
        ))->pluck('name')->all(),
        'mysql', 'mariadb' => collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
            ->map(fn ($row) => array_values((array) $row)[0] ?? null)
            ->filter()
            ->values()
            ->all(),
        'pgsql' => collect(DB::select(
            "select tablename as name from pg_catalog.pg_tables where schemaname = 'public' order by tablename"
        ))->pluck('name')->all(),
        default => throw new RuntimeException("Database backup is not configured for the [{$driver}] driver."),
    };

    $handle = gzopen($archivePath, 'wb9');
    if ($handle === false) {
        $this->error("Unable to write backup archive at {$archivePath}.");
        return 1;
    }

    $counts = [];

    try {
        $writeLine = function (array $payload) use ($handle): void {
            $line = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

            if ($line === false) {
                throw new RuntimeException('Unable to encode backup payload.');
            }

            gzwrite($handle, $line.PHP_EOL);
        };

        $writeLine([
            'type' => 'meta',
            'created_at' => now()->toIso8601String(),
            'connection' => $connection->getName(),
            'driver' => $driver,
            'database' => $connection->getDatabaseName(),
        ]);

        foreach ($tables as $table) {
            $counts[$table] = 0;

            foreach (DB::table($table)->cursor() as $row) {
                $writeLine([
                    'type' => 'row',
                    'table' => $table,
                    'data' => (array) $row,
                ]);

                $counts[$table]++;
            }
        }
    } finally {
        gzclose($handle);
    }

    $manifest = [
        'created_at' => now()->toIso8601String(),
        'archive' => $archivePath,
        'sha256' => hash_file('sha256', $archivePath),
        'connection' => $connection->getName(),
        'driver' => $driver,
        'database' => $connection->getDatabaseName(),
        'tables' => $counts,
    ];

    File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $keep = max(0, (int) $this->option('keep'));
    if ($keep > 0) {
        collect(File::files($backupDir))
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'pos-backup-') && str_ends_with($file->getFilename(), '.manifest.json'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->slice($keep)
            ->each(function ($file): void {
                File::delete($file->getPathname());
                File::delete(str_replace('.manifest.json', '.jsonl.gz', $file->getPathname()));
            });
    }

    $this->info("Backup created: {$archivePath}");

    return 0;
})->purpose('Create a compressed database backup snapshot for production operations');

Artisan::command('pos:import-switching-csv {dataset : categories|products|customers|inventory-items|opening-balances} {file : CSV file path} {--branch_id= : Default branch id for branch-scoped rows} {--restaurant_id= : Restaurant guard used with branch_id} {--dry-run : Validate and summarize without writing}', function () {
    $dataset = Str::of((string) $this->argument('dataset'))->lower()->toString();
    $file = (string) $this->argument('file');
    $branchId = $this->option('branch_id') ? (int) $this->option('branch_id') : null;
    $restaurantId = $this->option('restaurant_id') ? (int) $this->option('restaurant_id') : null;
    $dryRun = (bool) $this->option('dry-run');

    $allowed = ['categories', 'products', 'customers', 'inventory-items', 'opening-balances'];
    if (! in_array($dataset, $allowed, true)) {
        $this->error('Unsupported dataset. Use one of: '.implode(', ', $allowed));
        return 1;
    }

    if (! File::exists($file) || ! is_readable($file)) {
        $this->error("CSV file is not readable: {$file}");
        return 1;
    }

    if ($branchId) {
        $branch = \App\Models\Branch::query()->find($branchId);
        if (! $branch) {
            $this->error("Branch {$branchId} does not exist.");
            return 1;
        }

        if ($restaurantId && (int) $branch->restaurant_id !== $restaurantId) {
            $this->error("Branch {$branchId} does not belong to restaurant {$restaurantId}.");
            return 1;
        }
    }

    $handle = fopen($file, 'rb');
    if ($handle === false) {
        $this->error("Unable to open {$file}.");
        return 1;
    }

    $headers = fgetcsv($handle);
    if (! is_array($headers)) {
        fclose($handle);
        $this->error('CSV file is empty or missing a header row.');
        return 1;
    }

    $headers = array_map(fn ($header) => Str::of((string) $header)->trim()->lower()->replace([' ', '-'], '_')->toString(), $headers);
    $required = match ($dataset) {
        'categories' => ['name'],
        'products' => ['name', 'price'],
        'customers' => ['name'],
        'inventory-items', 'opening-balances' => ['name', 'unit', 'quantity'],
    };
    $missing = array_values(array_diff($required, $headers));
    if ($missing) {
        fclose($handle);
        $this->error('Missing required columns: '.implode(', ', $missing));
        return 1;
    }

    $summary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
    $errors = [];
    $lineNumber = 1;

    $normalizeBool = fn ($value, bool $fallback = true) => match (Str::of((string) $value)->trim()->lower()->toString()) {
        '1', 'true', 'yes', 'y', 'available', 'active' => true,
        '0', 'false', 'no', 'n', 'unavailable', 'inactive' => false,
        default => $fallback,
    };

    $rowBranchId = function (array $row) use ($branchId): ?int {
        $value = $row['branch_id'] ?? null;
        return filled($value) ? (int) $value : $branchId;
    };

    $guardBranch = function (?int $rowBranchId) use ($restaurantId): ?string {
        if (! $rowBranchId) {
            return null;
        }

        $branch = \App\Models\Branch::query()->find($rowBranchId);
        if (! $branch) {
            return "Branch {$rowBranchId} does not exist.";
        }

        if ($restaurantId && (int) $branch->restaurant_id !== $restaurantId) {
            return "Branch {$rowBranchId} does not belong to restaurant {$restaurantId}.";
        }

        return null;
    };

    $upsert = function (string $dataset, array $row) use ($rowBranchId, $guardBranch, $normalizeBool): string {
        $branchIdForRow = $rowBranchId($row);
        if ($message = $guardBranch($branchIdForRow)) {
            throw new RuntimeException($message);
        }

        return match ($dataset) {
            'categories' => (function () use ($row, $branchIdForRow) {
                $category = \App\Models\Category::query()->firstOrNew([
                    'branch_id' => $branchIdForRow,
                    'name' => trim((string) $row['name']),
                ]);
                $exists = $category->exists;
                $category->save();
                return $exists ? 'updated' : 'created';
            })(),
            'products' => (function () use ($row, $branchIdForRow, $normalizeBool) {
                $category = null;
                if (filled($row['category_name'] ?? null)) {
                    $category = \App\Models\Category::query()->firstOrCreate([
                        'branch_id' => $branchIdForRow,
                        'name' => trim((string) $row['category_name']),
                    ]);
                } elseif (filled($row['category_id'] ?? null)) {
                    $category = \App\Models\Category::query()->find((int) $row['category_id']);
                }

                $lookup = filled($row['sku'] ?? null)
                    ? ['sku' => trim((string) $row['sku'])]
                    : ['branch_id' => $branchIdForRow, 'name' => trim((string) $row['name'])];
                $product = \App\Models\Product::query()->firstOrNew($lookup);
                $exists = $product->exists;
                $product->fill([
                    'branch_id' => $branchIdForRow,
                    'category_id' => $category?->id,
                    'name' => trim((string) $row['name']),
                    'price' => (float) $row['price'],
                    'stock' => (int) ($row['stock'] ?? 0),
                    'min_stock' => (int) ($row['min_stock'] ?? 0),
                    'is_available' => $normalizeBool($row['is_available'] ?? true),
                    'image' => $row['image'] ?? $product->getRawOriginal('image'),
                    'sku' => filled($row['sku'] ?? null) ? trim((string) $row['sku']) : $product->sku,
                ]);
                $product->save();
                return $exists ? 'updated' : 'created';
            })(),
            'customers' => (function () use ($row) {
                $lookup = filled($row['phone'] ?? null)
                    ? ['phone' => trim((string) $row['phone'])]
                    : (filled($row['email'] ?? null)
                        ? ['email' => Str::lower(trim((string) $row['email']))]
                        : ['name' => trim((string) $row['name'])]);
                $customer = \App\Models\Customer::query()->firstOrNew($lookup);
                $exists = $customer->exists;
                $customer->fill([
                    'name' => trim((string) $row['name']),
                    'email' => filled($row['email'] ?? null) ? Str::lower(trim((string) $row['email'])) : $customer->email,
                    'phone' => filled($row['phone'] ?? null) ? trim((string) $row['phone']) : $customer->phone,
                    'loyalty_points' => (int) ($row['loyalty_points'] ?? $customer->loyalty_points ?? 0),
                ]);
                $customer->save();
                return $exists ? 'updated' : 'created';
            })(),
            'inventory-items', 'opening-balances' => (function () use ($row, $branchIdForRow) {
                $item = \App\Models\InventoryItem::query()->firstOrNew([
                    'branch_id' => $branchIdForRow,
                    'name' => trim((string) $row['name']),
                ]);
                $exists = $item->exists;
                $item->fill([
                    'unit' => trim((string) $row['unit']),
                    'quantity' => (float) $row['quantity'],
                    'min_stock' => (float) ($row['min_stock'] ?? $item->min_stock ?? 0),
                ]);
                $item->save();
                return $exists ? 'updated' : 'created';
            })(),
        };
    };

    DB::beginTransaction();
    try {
        while (($values = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if ($values === [null] || $values === false) {
                continue;
            }

            $row = array_combine($headers, array_pad($values, count($headers), null));
            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);

            try {
                $result = $upsert($dataset, $row);
                $summary[$result]++;
            } catch (Throwable $error) {
                $summary['errors']++;
                $errors[] = "Line {$lineNumber}: {$error->getMessage()}";
            }
        }

        if ($dryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }
    } catch (Throwable $error) {
        DB::rollBack();
        fclose($handle);
        throw $error;
    }

    fclose($handle);

    $this->info(($dryRun ? 'Dry-run validated' : 'Import completed')." for {$dataset}.");
    $this->table(['created', 'updated', 'skipped', 'errors'], [[
        $summary['created'],
        $summary['updated'],
        $summary['skipped'],
        $summary['errors'],
    ]]);

    foreach (array_slice($errors, 0, 20) as $error) {
        $this->warn($error);
    }

    if (count($errors) > 20) {
        $this->warn('Additional errors omitted: '.(count($errors) - 20));
    }

    return $summary['errors'] > 0 ? 1 : 0;
})->purpose('Validate and import competitor/spreadsheet switching CSV files');

Schedule::command('pos:backup')->dailyAt('02:00')->withoutOverlapping();
