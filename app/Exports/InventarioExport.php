<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * InventarioExport
 * Genera el reporte consolidado de inventario con 3 hojas:
 *   1. Stock Crítico
 *   2. Salidas No Comerciales
 *   3. Entradas vs Compras
 */
class InventarioExport
{
    public function __construct(
        private readonly Collection $stock,
        private readonly Collection $salidas,
        private readonly Collection $entradas,
        private readonly string     $from,
        private readonly string     $to,
    ) {}

    public function download(): BinaryFileResponse
    {
        $outputPath = $this->generate();

        return response()->download(
            $outputPath,
            $this->buildFilename(),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function generate(): string
    {
        $scriptPath = storage_path('scripts/generate_inventario.py');
        $outputPath = storage_path('exports/inventario_' . now()->timestamp . '.xlsx');

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $payload = json_encode([
            'stock'       => $this->stock->values()->toArray(),
            'salidas'     => $this->salidas->values()->toArray(),
            'entradas'    => $this->entradas->values()->toArray(),
            'from'        => $this->from,
            'to'          => $this->to,
            'client_name' => config('app_client.name'),
            'currency'    => config('app_client.locale.currency_symbol'),
            'output_path' => $outputPath,
        ], JSON_UNESCAPED_UNICODE);

        $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
        $process = proc_open(escapeshellcmd("python3 {$scriptPath}"), $descriptors, $pipes);

        if (! is_resource($process)) {
            throw new \RuntimeException('No se pudo iniciar el proceso de exportación de inventario.');
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! file_exists($outputPath)) {
            \Illuminate\Support\Facades\Log::error('[InventarioExport] Error al generar Excel', ['stderr' => $stderr]);
            throw new \RuntimeException('Error al generar el reporte de inventario.');
        }

        return $outputPath;
    }

    private function buildFilename(): string
    {
        return 'Inventario_' . config('app_client.short_name', 'BI') . "_{$this->from}_{$this->to}.xlsx";
    }
}
