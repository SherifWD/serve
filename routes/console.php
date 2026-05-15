<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

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

Schedule::command('pos:backup')->dailyAt('02:00')->withoutOverlapping();
