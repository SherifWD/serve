<?php

namespace App\Support;

use App\Models\Device;
use App\Models\PaymentProviderConfig;
use Illuminate\Validation\ValidationException;

class HardwareValidation
{
    public const PAYMENT_METHODS = ['cash', 'card', 'wallet'];

    public const PRINTER_PROFILES = [
        'browser-print',
        'cash-drawer',
        'epson-thermal',
        'escpos-network',
        'escpos-usb',
        'pdf',
        'system-printer',
    ];

    public static function validatePrinterProfile(?string $profile, string $field = 'printer_profile'): void
    {
        if (!$profile) {
            return;
        }

        if (!in_array($profile, self::PRINTER_PROFILES, true)) {
            throw ValidationException::withMessages([
                $field => 'Unsupported printer profile.',
            ]);
        }
    }

    public static function validatePrinterEndpoint(?string $endpoint, string $field = 'printer_endpoint'): void
    {
        if (!$endpoint) {
            return;
        }

        $parts = parse_url($endpoint);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $allowedSchemes = ['tcp', 'socket', 'usb', 'ipp', 'ipps', 'lpd', 'file'];

        if (!in_array($scheme, $allowedSchemes, true)) {
            throw ValidationException::withMessages([
                $field => 'Printer endpoint must start with tcp://, socket://, usb://, ipp://, ipps://, lpd://, or file://.',
            ]);
        }

        if (in_array($scheme, ['tcp', 'socket'], true)) {
            $host = $parts['host'] ?? null;
            $port = $parts['port'] ?? null;

            if (!$host || !$port || $port < 1 || $port > 65535) {
                throw ValidationException::withMessages([
                    $field => 'Network printer endpoint must include a host and valid port, for example tcp://192.168.1.20:9100.',
                ]);
            }
        }
    }

    public static function validatePaymentProviderForAttempt(
        ?PaymentProviderConfig $provider,
        string $method,
        ?Device $device = null
    ): void {
        if ($method === 'cash') {
            return;
        }

        if (!$provider) {
            throw ValidationException::withMessages([
                'provider' => 'An active payment provider is required for card or wallet payments.',
            ]);
        }

        if (!$provider->is_active) {
            throw ValidationException::withMessages([
                'provider' => 'Payment provider is inactive.',
            ]);
        }

        $supportedMethods = $provider->supported_methods ?? [];
        if (!in_array($method, $supportedMethods, true)) {
            throw ValidationException::withMessages([
                'method' => 'Payment method is not supported by the selected provider.',
            ]);
        }

        if ($provider->mode === 'terminal' && !$device) {
            throw ValidationException::withMessages([
                'device_uuid' => 'Terminal payments require a registered branch device.',
            ]);
        }

        if ($device && !$device->is_active) {
            throw ValidationException::withMessages([
                'device_uuid' => 'Selected terminal device is inactive.',
            ]);
        }
    }
}
