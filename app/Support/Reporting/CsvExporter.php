<?php

namespace App\Support\Reporting;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * Stream a CSV download response for the provided dataset.
     *
     * @param  string  $filename
     * @param  array<string>  $headers
     * @param  iterable<array<string, mixed>>  $rows
     */
    public static function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $callback = static function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $key) {
                    $line[] = data_get($row, $key, '');
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        };

        $filename = str()->finish(pathinfo($filename, PATHINFO_FILENAME), '.csv');

        return response()->streamDownload(
            $callback,
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }
}

