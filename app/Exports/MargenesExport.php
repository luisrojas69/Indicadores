<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * MargenesExport
 *
 * Genera el reporte Excel de márgenes y bono mensual.
 *
 * Estrategia: ejecuta un script Python (generate_margenes.py) vía proc_open,
 * pasando los datos como JSON. El script usa openpyxl para producir un .xlsx
 * profesional con formato financiero, fórmulas y semáforo de colores.
 *
 * ¿Por qué Python y no una librería PHP?
 *   - PhpSpreadsheet es muy lento con colecciones grandes (>1000 filas).
 *   - openpyxl produce archivos más livianos y con mejor soporte de fórmulas.
 *   - El script reside en storage/scripts/ — controlado, no accesible por web.
 *
 * Alternativa: si el cliente tiene PhpSpreadsheet instalado y prefiere PHP puro,
 * se puede intercambiar esta clase sin cambiar el controlador.
 */
class MargenesExport
{
    public function __construct(
        private readonly Collection $margenes,
        private readonly array      $resumenBono,
        private readonly string     $from,
        private readonly string     $to,
        private readonly string     $costField,
    ) {}

    /**
     * Genera el archivo y retorna una respuesta de descarga.
     */
    public function download(): BinaryFileResponse
    {
        $outputPath = $this->generate();

        return response()->download(
            $outputPath,
            $this->buildFilename(),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    /**
     * Ejecuta el script Python y retorna la ruta del archivo generado.
     */
    private function generate(): string
    {
        $scriptPath = storage_path('scripts/generate_margenes.py');
        $outputPath = storage_path('exports/margenes_' . now()->timestamp . '.xlsx');

        // Asegurar que el directorio de salida exista
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $payload = json_encode([
            'margenes'      => $this->margenes->values()->toArray(),
            'resumen'       => $this->resumenBono,
            'from'          => $this->from,
            'to'            => $this->to,
            'cost_field'    => $this->costField,
            'client_name'   => config('app_client.name'),
            'currency'      => config('app_client.locale.currency_symbol'),
            'iva_rate'      => config('app_client.business.iva_rate'),
            'alert_red'     => config('app_client.business.margin_alert_red'),
            'alert_yellow'  => config('app_client.business.margin_alert_yellow'),
            'output_path'   => $outputPath,
        ], JSON_UNESCAPED_UNICODE);

        // Ejecutar script Python
        $command     = escapeshellcmd("python3 {$scriptPath}");
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (! is_resource($process)) {
            throw new \RuntimeException('No se pudo iniciar el proceso de exportación.');
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! file_exists($outputPath)) {
            \Illuminate\Support\Facades\Log::error('[MargenesExport] Falló la generación del Excel', [
                'stdout' => $stdout,
                'stderr' => $stderr,
                'exit'   => $exitCode,
            ]);
            throw new \RuntimeException('Error al generar el reporte Excel. Revise los logs.');
        }

        return $outputPath;
    }

    private function buildFilename(): string
    {
        $client = str_replace(' ', '_', config('app_client.short_name', 'BI'));
        return "Margenes_{$client}_{$this->from}_{$this->to}.xlsx";
    }
}
