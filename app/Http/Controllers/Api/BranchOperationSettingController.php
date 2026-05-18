<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\Process\Process;

class BranchOperationSettingController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $query = Branch::query()
            ->with([
                'restaurant:id,name,kind,logo_url',
                'cashRegister',
                'devices' => fn ($devices) => $devices
                    ->whereIn('type', ['Receipt Printer', 'Printer', 'Cash Drawer'])
                    ->orderBy('name'),
            ])
            ->orderBy('name');

        $branchIds = $this->accessibleBranchIds($request);
        if ($branchIds !== null) {
            $query->whereIn('id', $branchIds);
        }

        if ($request->user()?->isPlatformAdmin()) {
            if ($request->filled('restaurant_id')) {
                $query->where('restaurant_id', $request->integer('restaurant_id'));
            }

            if ($request->filled('branch_id')) {
                $query->whereKey($request->integer('branch_id'));
            }
        }

        return response()->json([
            'data' => $query->get()->map(fn (Branch $branch) => $this->resource($branch))->values(),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $this->ensureBranchAccess($request, (int) $branch->id);

        $receiptPrinter = $this->receiptPrinterFor($branch);
        $cashDrawerDevice = $this->cashDrawerDeviceFor($branch);

        $receiptPrinterUuidRule = Rule::unique('devices', 'uuid');
        if ($receiptPrinter) {
            $receiptPrinterUuidRule->ignore($receiptPrinter->id);
        }

        $cashDrawerUuidRule = Rule::unique('devices', 'uuid');
        if ($cashDrawerDevice) {
            $cashDrawerUuidRule->ignore($cashDrawerDevice->id);
        }

        $data = $request->validate([
            'cash_drawer' => 'required|array',
            'cash_drawer.opening_balance' => 'nullable|numeric|min:0|max:99999999.99',
            'cash_drawer.closing_balance' => 'nullable|numeric|min:0|max:99999999.99',
            'cash_drawer.is_open' => 'nullable|boolean',
            'cash_drawer.name' => 'nullable|string|max:255',
            'cash_drawer.uuid' => ['nullable', 'string', 'max:255', $cashDrawerUuidRule],
            'cash_drawer.printer_endpoint' => 'nullable|string|max:255',
            'cash_drawer.is_active' => 'nullable|boolean',
            'receipt_printer' => 'required|array',
            'receipt_printer.name' => 'nullable|string|max:255',
            'receipt_printer.uuid' => ['nullable', 'string', 'max:255', $receiptPrinterUuidRule],
            'receipt_printer.printer_profile' => 'nullable|string|max:100',
            'receipt_printer.printer_paper_width_mm' => 'nullable|integer|min:40|max:120',
            'receipt_printer.printer_endpoint' => 'nullable|string|max:255',
            'receipt_printer.is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($branch, $data, $receiptPrinter, $cashDrawerDevice): void {
            $drawer = $data['cash_drawer'];
            CashRegister::query()->updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'opening_balance' => $drawer['opening_balance'] ?? 0,
                    'closing_balance' => $drawer['closing_balance'] ?? null,
                    'is_open' => $drawer['is_open'] ?? true,
                ],
            );

            $cashDrawerDevice ??= new Device(['branch_id' => $branch->id]);
            $cashDrawerDevice->forceFill([
                'branch_id' => $branch->id,
                'name' => ($drawer['name'] ?? null) ?: "{$branch->name} Cash Drawer",
                'type' => 'Cash Drawer',
                'uuid' => ($drawer['uuid'] ?? null) ?: "branch-{$branch->id}-cash-drawer",
                'printer_profile' => null,
                'printer_paper_width_mm' => null,
                'printer_endpoint' => $drawer['printer_endpoint'] ?? null,
                'capabilities' => ['cash_drawer' => true],
                'is_active' => $drawer['is_active'] ?? true,
            ])->save();

            $printer = $data['receipt_printer'];
            $receiptPrinter ??= new Device(['branch_id' => $branch->id]);
            $receiptPrinter->forceFill([
                'branch_id' => $branch->id,
                'name' => ($printer['name'] ?? null) ?: "{$branch->name} Receipt Printer",
                'type' => 'Receipt Printer',
                'uuid' => ($printer['uuid'] ?? null) ?: "branch-{$branch->id}-receipt-printer",
                'printer_profile' => ($printer['printer_profile'] ?? null) ?: 'epson-thermal',
                'printer_paper_width_mm' => $printer['printer_paper_width_mm'] ?? 80,
                'printer_endpoint' => $printer['printer_endpoint'] ?? null,
                'capabilities' => ['receipt_printer' => true],
                'is_active' => $printer['is_active'] ?? true,
            ])->save();
        });

        return response()->json([
            'data' => $this->resource($branch->fresh([
                'restaurant:id,name,kind,logo_url',
                'cashRegister',
                'devices',
            ])),
        ]);
    }

    public function discover(Request $request, Branch $branch)
    {
        $this->ensureBranchAccess($request, (int) $branch->id);

        $data = $request->validate([
            'mode' => 'nullable|in:all,local,network',
            'network_target' => 'nullable|string|max:64',
            'ports' => 'nullable|array|max:5',
            'ports.*' => 'integer|min:1|max:65535',
        ]);

        $mode = $data['mode'] ?? 'all';
        $ports = array_values(array_unique($data['ports'] ?? [9100, 515, 631]));
        $devices = collect();

        if (in_array($mode, ['all', 'local'], true)) {
            $devices = $devices->merge($this->discoverLocalPrinters($branch));
        }

        if (in_array($mode, ['all', 'network'], true) && filled($data['network_target'] ?? null)) {
            $devices = $devices->merge($this->discoverNetworkPrinters($branch, $data['network_target'], $ports));
        }

        return response()->json([
            'data' => $devices
                ->unique('uuid')
                ->values()
                ->all(),
            'meta' => [
                'branch_id' => $branch->id,
                'mode' => $mode,
                'network_target' => $data['network_target'] ?? null,
                'network_target_required' => in_array($mode, ['all', 'network'], true) && blank($data['network_target'] ?? null),
            ],
        ]);
    }

    private function resource(Branch $branch): array
    {
        $cashRegister = $branch->cashRegister;
        $receiptPrinter = $this->receiptPrinterFor($branch);
        $cashDrawerDevice = $this->cashDrawerDeviceFor($branch);

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'location' => $branch->location,
            'restaurant' => $branch->restaurant,
            'cash_drawer' => [
                'register_id' => $cashRegister?->id,
                'opening_balance' => (float) ($cashRegister?->opening_balance ?? 0),
                'closing_balance' => $cashRegister?->closing_balance !== null
                    ? (float) $cashRegister->closing_balance
                    : null,
                'is_open' => (bool) ($cashRegister?->is_open ?? true),
                'device_id' => $cashDrawerDevice?->id,
                'name' => $cashDrawerDevice?->name ?? "{$branch->name} Cash Drawer",
                'uuid' => $cashDrawerDevice?->uuid ?? "branch-{$branch->id}-cash-drawer",
                'printer_endpoint' => $cashDrawerDevice?->printer_endpoint,
                'is_active' => (bool) ($cashDrawerDevice?->is_active ?? true),
            ],
            'receipt_printer' => [
                'device_id' => $receiptPrinter?->id,
                'name' => $receiptPrinter?->name ?? "{$branch->name} Receipt Printer",
                'uuid' => $receiptPrinter?->uuid ?? "branch-{$branch->id}-receipt-printer",
                'printer_profile' => $receiptPrinter?->printer_profile ?? 'epson-thermal',
                'printer_paper_width_mm' => $receiptPrinter?->printer_paper_width_mm ?? 80,
                'printer_endpoint' => $receiptPrinter?->printer_endpoint,
                'is_active' => (bool) ($receiptPrinter?->is_active ?? true),
            ],
        ];
    }

    private function receiptPrinterFor(Branch $branch): ?Device
    {
        if ($branch->relationLoaded('devices')) {
            return $branch->devices
                ->first(fn (Device $device) => in_array($device->type, ['Receipt Printer', 'Printer'], true));
        }

        return Device::query()
            ->where('branch_id', $branch->id)
            ->whereIn('type', ['Receipt Printer', 'Printer'])
            ->orderByRaw("case when type = 'Receipt Printer' then 0 else 1 end")
            ->first();
    }

    private function cashDrawerDeviceFor(Branch $branch): ?Device
    {
        if ($branch->relationLoaded('devices')) {
            return $branch->devices->firstWhere('type', 'Cash Drawer');
        }

        return Device::query()
            ->where('branch_id', $branch->id)
            ->where('type', 'Cash Drawer')
            ->first();
    }

    private function discoverLocalPrinters(Branch $branch): array
    {
        $printers = [];

        if (PHP_OS_FAMILY === 'Windows') {
            $printers = $this->discoverWindowsPrinters();
        } else {
            $printers = $this->discoverUnixPrinters();
        }

        return collect($printers)
            ->map(fn (array $printer) => $this->discoveredPrinterPayload(
                branch: $branch,
                name: $printer['name'],
                endpoint: $printer['endpoint'],
                source: 'local',
                profile: $printer['profile'] ?? 'system-printer',
            ))
            ->values()
            ->all();
    }

    private function discoverUnixPrinters(): array
    {
        $output = $this->runCommand(['lpstat', '-v']);
        if ($output === null) {
            return [];
        }

        return collect(preg_split('/\R/', trim($output)))
            ->map(function (string $line): ?array {
                if (! preg_match('/^device for (.+?):\s*(.+)$/', trim($line), $matches)) {
                    return null;
                }

                return [
                    'name' => trim($matches[1]),
                    'endpoint' => trim($matches[2]),
                    'profile' => 'system-printer',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function discoverWindowsPrinters(): array
    {
        $output = $this->runCommand([
            'powershell',
            '-NoProfile',
            '-Command',
            'Get-Printer | Select-Object Name,PortName | ConvertTo-Json -Compress',
        ]);

        if ($output === null) {
            return [];
        }

        $decoded = json_decode($output, true);
        if (! is_array($decoded)) {
            return [];
        }

        $rows = array_is_list($decoded) ? $decoded : [$decoded];

        return collect($rows)
            ->map(function (array $row): ?array {
                $name = $row['Name'] ?? null;
                if (! $name) {
                    return null;
                }

                return [
                    'name' => $name,
                    'endpoint' => 'windows-printer://'.rawurlencode($name),
                    'profile' => 'system-printer',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function runCommand(array $command): ?string
    {
        try {
            $process = new Process($command);
            $process->setTimeout(4);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            return $process->getOutput();
        } catch (\Throwable) {
            return null;
        }
    }

    private function discoverNetworkPrinters(Branch $branch, string $target, array $ports): array
    {
        $hosts = $this->hostsForTarget($target);

        return collect($hosts)
            ->flatMap(function (string $host) use ($branch, $ports) {
                return collect($ports)
                    ->filter(fn (int $port) => $this->canConnect($host, $port))
                    ->map(fn (int $port) => $this->discoveredPrinterPayload(
                        branch: $branch,
                        name: "Network printer {$host}:{$port}",
                        endpoint: $this->endpointForPort($host, $port),
                        source: 'network',
                        profile: $this->profileForPort($port),
                        port: $port,
                    ));
            })
            ->values()
            ->all();
    }

    private function hostsForTarget(string $target): array
    {
        $target = trim($target);

        if (str_contains($target, '/')) {
            [$baseIp, $prefix] = explode('/', $target, 2);
            $prefix = (int) $prefix;

            abort_unless($this->isPrivateIpv4($baseIp), 422, 'Network discovery is limited to private LAN ranges.');
            abort_unless($prefix >= 26 && $prefix <= 32, 422, 'Use a /26 or smaller CIDR range for discovery.');

            $base = ip2long($baseIp);
            $mask = -1 << (32 - $prefix);
            $network = $base & $mask;
            $broadcast = $network + (2 ** (32 - $prefix)) - 1;

            $start = $prefix === 32 ? $network : $network + 1;
            $end = $prefix === 32 ? $network : $broadcast - 1;

            $hosts = [];
            for ($ip = $start; $ip <= $end; $ip++) {
                $hosts[] = long2ip($ip);
            }

            return $hosts;
        }

        abort_unless($this->isPrivateIpv4($target), 422, 'Network discovery is limited to private LAN ranges.');

        return [$target];
    }

    private function isPrivateIpv4(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $long = ip2long($ip);
        $ranges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['127.0.0.0', '127.255.255.255'],
            ['169.254.0.0', '169.254.255.255'],
        ];

        foreach ($ranges as [$start, $end]) {
            if ($long >= ip2long($start) && $long <= ip2long($end)) {
                return true;
            }
        }

        return false;
    }

    private function canConnect(string $host, int $port): bool
    {
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $error,
            0.08,
            STREAM_CLIENT_CONNECT
        );

        if (! $socket) {
            return false;
        }

        fclose($socket);

        return true;
    }

    private function endpointForPort(string $host, int $port): string
    {
        return match ($port) {
            631 => "ipp://{$host}:{$port}/ipp/print",
            515 => "lpd://{$host}:{$port}",
            default => "tcp://{$host}:{$port}",
        };
    }

    private function profileForPort(int $port): string
    {
        return match ($port) {
            631 => 'ipp-printer',
            515 => 'lpd-printer',
            default => 'escpos-network',
        };
    }

    private function discoveredPrinterPayload(
        Branch $branch,
        string $name,
        string $endpoint,
        string $source,
        string $profile,
        ?int $port = null,
    ): array {
        $fingerprint = Str::slug($source.'-'.$endpoint);
        if (strlen($fingerprint) > 96) {
            $fingerprint = Str::slug($source).'-'.substr(sha1($endpoint), 0, 20);
        }

        return [
            'name' => $name,
            'type' => 'Receipt Printer',
            'uuid' => "branch-{$branch->id}-{$fingerprint}",
            'printer_profile' => $profile,
            'printer_paper_width_mm' => 80,
            'printer_endpoint' => $endpoint,
            'capabilities' => [
                'receipt_printer' => true,
                'cash_drawer' => in_array($profile, ['escpos-network', 'epson-thermal', 'system-printer'], true),
            ],
            'is_active' => true,
            'source' => $source,
            'port' => $port,
        ];
    }
}
