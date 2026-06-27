<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\ToolCatalog;
use App\Erp\Contracts\ErpConnectionInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ToolDispatcher
 *
 * Ejecutor seguro de herramientas del ERP.
 *
 * Responsabilidades:
 *   1. Verificar que el tool_name esté en el ToolCatalog (whitelist)
 *   2. Sanitizar y validar los argumentos antes de pasarlos al ERP
 *   3. Mapear el nombre de la tool al método correcto de ErpConnectionInterface
 *   4. Retornar siempre array plano (nunca Collection — evita el bug de caché)
 *
 * REGLA DE ORO: Ningún valor generado por la IA llega directamente a una
 * query SQL. Todo pasa por este dispatcher que valida tipos y formatos.
 */
class ToolDispatcher
{
    public function __construct(
        private readonly ErpConnectionInterface $erp,
    ) {}

    /**
     * Ejecuta una herramienta por nombre con los argumentos dados.
     *
     * @param  string  $toolName  Nombre de la tool (validado contra whitelist)
     * @param  array   $args      Argumentos generados por la IA
     * @return array              Resultado plano listo para serializar a JSON
     *
     * @throws \InvalidArgumentException  Si la tool no existe en el catálogo
     * @throws \RuntimeException          Si el ERP falla al ejecutar la consulta
     */
    public function execute(string $toolName, array $args): array
    {
        // ── 1. Whitelist estricta ─────────────────────────────────────────
        if (!ToolCatalog::exists($toolName)) {
            Log::warning('[ToolDispatcher] Intento de ejecutar tool no registrada', [
                'tool_name' => $toolName,
                'args'      => $args,
            ]);
            throw new \InvalidArgumentException(
                "La herramienta '{$toolName}' no está registrada en el catálogo."
            );
        }

        // ── 2. Resolver y ejecutar ────────────────────────────────────────
        Log::info('[ToolDispatcher] Ejecutando tool', [
            'tool' => $toolName,
            'args' => $args,
        ]);

        $result = match ($toolName) {
            'get_dashboard_kpis'          => $this->runDashboardKpis($args),
            'get_ranking_vendedores'      => $this->runRankingVendedores($args),
            'get_top_productos'           => $this->runTopProductos($args),
            'get_cxc_summary'             => $this->runCxcSummary($args),
            'get_margenes_por_articulo'   => $this->runMargenes($args),
            'get_resumen_financiero'      => $this->runResumenFinanciero($args),
            'get_stock_critico'           => $this->runStockCritico(),
            'get_entradas_vs_compras'     => $this->runEntradasVsCompras($args),
            'get_salidas_no_comerciales'  => $this->runSalidasNoComerciales($args),
            default => throw new \InvalidArgumentException(
                "No hay implementación para la tool '{$toolName}'."
            ),
        };

        // ── 3. Garantizar array plano de salida ───────────────────────────
        return $this->normalize($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RUNNERS — uno por herramienta
    // Cada método valida sus argumentos antes de llamar al ERP.
    // ─────────────────────────────────────────────────────────────────────────

    private function runDashboardKpis(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        $result = $this->erp->getDashboardKpis($from, $to);

        // getDashboardKpis retorna array asociativo — lo envolvemos para consistencia
        return [$result];
    }

    private function runRankingVendedores(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        return $this->erp->getRankingVendedores($from, $to)->values()->toArray();
    }

    private function runTopProductos(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        // Sanitizar limit — la IA puede mandar cualquier número
        $limit = isset($args['limit']) ? (int) $args['limit'] : 10;
        $limit = max(1, min(20, $limit)); // Clamp entre 1 y 20

        return $this->erp->getTopProductos($from, $to, $limit)->values()->toArray();
    }

    private function runCxcSummary(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        return [$this->erp->getCuentasPorCobrarSummary($from, $to)];
    }

    private function runMargenes(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        // Whitelist del campo de costo — nunca interpolamos lo que manda la IA
        $allowedCostFields = ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'];
        $costField = strtoupper(trim($args['cost_field'] ?? 'COS_PRO_UN'));

        if (!in_array($costField, $allowedCostFields, true)) {
            $costField = 'COS_PRO_UN';
        }

        return $this->erp->getMargenesPorArticulo($from, $to, $costField)->values()->toArray();
    }

    private function runResumenFinanciero(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        $allowedCostFields = ['COS_PRO_UN', 'ULT_COS_UN', 'COS_PRO_OM', 'ULT_COS_OM'];
        $costField = strtoupper(trim($args['cost_field'] ?? 'COS_PRO_UN'));

        if (!in_array($costField, $allowedCostFields, true)) {
            $costField = 'COS_PRO_UN';
        }

        return [$this->erp->getResumenFinanciero($from, $to, $costField)];
    }

    private function runStockCritico(): array
    {
        // Sin parámetros de fecha — trabaja sobre el estado actual del inventario
        return $this->erp->getStockCritico()->values()->toArray();
    }

    private function runEntradasVsCompras(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        return $this->erp->getEntradasVsCompras($from, $to)->values()->toArray();
    }

    private function runSalidasNoComerciales(array $args): array
    {
        [$from, $to] = $this->resolveDates($args);

        return $this->erp->getSalidasNoComerciales($from, $to)->values()->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extrae y valida las fechas de los argumentos de la IA.
     * La IA puede mandar 'date_from'/'date_to' o 'from'/'to' — los normalizamos.
     * Carbon valida el formato — si la fecha es inválida, usa valores seguros por defecto.
     *
     * @return array{0: string, 1: string}  [$from, $to] en formato Y-m-d
     */
    private function resolveDates(array $args): array
    {
        // La IA puede usar diferentes keys según el tool definition
        $rawFrom = $args['date_from'] ?? $args['from'] ?? null;
        $rawTo   = $args['date_to']   ?? $args['to']   ?? null;

        try {
            $from = $rawFrom
                ? Carbon::parse($rawFrom)->toDateString()
                : now()->startOfMonth()->toDateString();
        } catch (\Throwable) {
            $from = now()->startOfMonth()->toDateString();
        }

        try {
            $to = $rawTo
                ? Carbon::parse($rawTo)->toDateString()
                : now()->toDateString();
        } catch (\Throwable) {
            $to = now()->toDateString();
        }

        // Garantizar que from <= to
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        // No permitir fechas futuras mayores a hoy
        $today = now()->toDateString();
        if ($to > $today) {
            $to = $today;
        }

        return [$from, $to];
    }

    /**
     * Normaliza el resultado a array plano.
     * Garantiza que nunca llegue una Collection al controller
     * (lección aprendida del bug de caché con __PHP_Incomplete_Class).
     *
     * @param  mixed  $result
     * @return array
     */
    private function normalize(mixed $result): array
    {
        if ($result instanceof \Illuminate\Support\Collection) {
            return $result->values()->toArray();
        }

        if (is_array($result)) {
            return $result;
        }

        // Escalar suelto (raro, pero seguro)
        return [['value' => $result]];
    }
}
