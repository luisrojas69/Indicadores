<?php

declare(strict_types=1);

namespace App\Services\Financiero;

use Illuminate\Support\Collection;

/**
 * MargenService
 *
 * Encapsula toda la lógica financiera del módulo de márgenes:
 *   1. Normalización de precios (con/sin IVA)
 *   2. Clasificación semáforo de margen (rojo/amarillo/verde)
 *   3. Cálculo del resumen de bono mensual
 *   4. Cálculo implícito IVA para Venezuela (precio base + alícuota)
 *
 * Esta clase no toca la BD. Recibe colecciones ya hidratadas desde
 * ErpConnectionInterface y devuelve datos listos para la vista y el export.
 *
 * Contexto Venezuela:
 *   - El IVA actual es 16%. Puede variar por decreto.
 *   - Profit Plus puede tener los precios con o sin IVA incluido
 *     dependiendo de la configuración del cliente → BUSINESS_PRICES_INCLUDE_IVA
 *   - Para el margen de rentabilidad, siempre trabajamos con precio SIN IVA
 *     (el IVA no es ingreso del negocio, es un pasivo fiscal).
 */
class MargenService
{
    private float $ivaRate;
    private bool  $pricesIncludeIva;
    private float $alertRed;
    private float $alertYellow;

    public function __construct()
    {
        $this->ivaRate          = (float) config('app_client.business.iva_rate',           16);
        $this->pricesIncludeIva = (bool)  config('app_client.business.prices_include_iva', false);
        $this->alertRed         = (float) config('app_client.business.margin_alert_red',   10);
        $this->alertYellow      = (float) config('app_client.business.margin_alert_yellow', 20);
    }

    /*
    |--------------------------------------------------------------------------
    | IVA
    |--------------------------------------------------------------------------
    */

    /**
     * Retorna el factor multiplicador del IVA (ej: 16% → 1.16).
     */
    public function ivaFactor(): float
    {
        return 1 + ($this->ivaRate / 100);
    }

    /**
     * Extrae el precio base (sin IVA) desde un precio dado.
     * Si los precios de Profit ya vienen sin IVA, devuelve el mismo valor.
     */
    public function precioSinIva(float $precio): float
    {
        if (! $this->pricesIncludeIva) {
            return $precio;
        }

        return round($precio / $this->ivaFactor(), 6);
    }

    /**
     * Calcula el precio con IVA desde un precio base.
     */
    public function precioConIva(float $precioBase): float
    {
        return round($precioBase * $this->ivaFactor(), 6);
    }

    /*
    |--------------------------------------------------------------------------
    | Enriquecimiento de colección de márgenes
    |--------------------------------------------------------------------------
    */

    /**
     * Enriquece la colección bruta de márgenes con:
     *   - precio_sin_iva / precio_con_iva
     *   - margen recalculado sobre precio sin IVA
     *   - semáforo de alerta
     *   - indicador de si el margen es negativo
     *
     * @param  Collection<int, array<string, mixed>>  $raw  Datos crudos de ErpConnectionInterface::getMargenesPorArticulo()
     * @param  bool  $excluirIva  Si true, recalcula el margen sobre precio sin IVA
     * @return Collection<int, array<string, mixed>>
     */
    public function enriquecerMargenes(Collection $raw, bool $excluirIva = false): Collection
    {
        return $raw->map(function (array $item) use ($excluirIva) {
            $precioOriginal = (float) $item['precio_venta'];
            $costo          = (float) $item['costo'];

            // Precio base para cálculo de margen
            $precioCalculo = $excluirIva
                ? $this->precioSinIva($precioOriginal)
                : $precioOriginal;

            // Recalcular margen sobre el precio normalizado
            $margenMonto = $precioCalculo - $costo;
            $margenPct   = $precioCalculo > 0
                ? round($margenMonto / $precioCalculo * 100, 2)
                : 0.0;

            return array_merge($item, [
                'precio_sin_iva'  => $this->precioSinIva($precioOriginal),
                'precio_con_iva'  => $this->precioConIva($this->precioSinIva($precioOriginal)),
                'precio_calculo'  => $precioCalculo,
                'margen_monto'    => round($margenMonto, 4),
                'margen_pct'      => $margenPct,
                'semaforo'        => $this->semaforo($margenPct),
                'es_negativo'     => $margenMonto < 0,
                'iva_excluido'    => $excluirIva,
                'iva_rate'        => $this->ivaRate,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Semáforo de margen
    |--------------------------------------------------------------------------
    */

    /**
     * Clasifica un margen porcentual en: 'verde' | 'amarillo' | 'rojo'
     * Los umbrales se toman de config/app_client.php (editables desde UI).
     */
    public function semaforo(float $margenPct): string
    {
        if ($margenPct >= $this->alertYellow) return 'verde';
        if ($margenPct >= $this->alertRed)    return 'amarillo';
        return 'rojo';
    }

    /**
     * Cuenta artículos por semáforo en una colección ya enriquecida.
     *
     * @return array{verde: int, amarillo: int, rojo: int, negativos: int}
     */
    public function conteoSemaforo(Collection $margenes): array
    {
        return [
            'verde'    => $margenes->where('semaforo', 'verde')->count(),
            'amarillo' => $margenes->where('semaforo', 'amarillo')->count(),
            'rojo'     => $margenes->where('semaforo', 'rojo')->count(),
            'negativos'=> $margenes->where('es_negativo', true)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resumen del Bono Mensual
    |--------------------------------------------------------------------------
    */

    /**
     * Calcula el resumen financiero del período para el cálculo del bono.
     *
     * El bono se calcula sobre el margen del mes completo (no por vendedor).
     * Fórmula:
     *   Ganancia Neta = SUM(precio_calculo * unidades) - SUM(costo * unidades)
     *   Margen Neto % = Ganancia Neta / Total Facturado × 100
     *
     * @param  Collection<int, array<string, mixed>>  $margenes  Colección YA enriquecida
     * @param  array<string, mixed>  $resumenErp  Datos brutos del ERP (totales globales)
     * @param  bool  $excluirIva
     * @return array<string, mixed>
     */
    public function calcularResumenBono(
        Collection $margenes,
        array $resumenErp,
        bool $excluirIva = false
    ): array {
        // Usamos los totales del ERP como base y aplicamos la corrección de IVA
        $totalFacturado = (float) ($resumenErp['total_facturado'] ?? 0);
        $costoTotal     = (float) ($resumenErp['costo_total']     ?? 0);

        // Si los precios incluyen IVA y el usuario pide excluirlo,
        // dividimos el total facturado por el factor IVA
        $totalBase = $excluirIva
            ? $this->precioSinIva($totalFacturado)
            : $totalFacturado;

        $gananciaNeta  = $totalBase - $costoTotal;
        $margenNetoPct = $totalBase > 0
            ? round($gananciaNeta / $totalBase * 100, 2)
            : 0.0;

        // Conteo de artículos por semáforo
        $semaforos = $this->conteoSemaforo($margenes);

        // Artículo con mayor margen y con menor margen (para destacar en UI)
        $mejorArticulo = $margenes->sortByDesc('margen_pct')->first();
        $peorArticulo  = $margenes->where('es_negativo', false)->sortBy('margen_pct')->first();

        return [
            // Financiero
            'total_facturado'   => $totalFacturado,
            'total_base'        => $totalBase,       // sin IVA si se excluyó
            'costo_total'       => $costoTotal,
            'ganancia_neta'     => $gananciaNeta,
            'margen_neto_pct'   => $margenNetoPct,
            'iva_excluido'      => $excluirIva,
            'iva_rate'          => $this->ivaRate,
            'iva_monto'         => $excluirIva ? round($totalFacturado - $totalBase, 2) : 0.0,

            // Semáforo
            'semaforo_conteo'   => $semaforos,
            'total_articulos'   => $margenes->count(),

            // Destaque
            'mejor_articulo'    => $mejorArticulo ? [
                'codigo'      => $mejorArticulo['codigo'],
                'descripcion' => $mejorArticulo['descripcion'],
                'margen_pct'  => $mejorArticulo['margen_pct'],
            ] : null,
            'peor_articulo'     => $peorArticulo ? [
                'codigo'      => $peorArticulo['codigo'],
                'descripcion' => $peorArticulo['descripcion'],
                'margen_pct'  => $peorArticulo['margen_pct'],
            ] : null,

            // Clasificación del margen global (semáforo a nivel empresa)
            'semaforo_global'   => $this->semaforo($margenNetoPct),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidades de formato (para usar en exports sin depender de Blade)
    |--------------------------------------------------------------------------
    */

    /**
     * Formatea un número con la configuración de locale del cliente.
     */
    public function formatMoney(float $value): string
    {
        $symbol = config('app_client.locale.currency_symbol', '$');
        $dec    = config('app_client.locale.decimal_sep',    ',');
        $thou   = config('app_client.locale.thousands_sep',  '.');

        return $symbol . ' ' . number_format($value, 2, $dec, $thou);
    }

    public function formatPct(float $value): string
    {
        return number_format($value, 2, ',', '.') . '%';
    }

    /**
     * Expone los umbrales de alerta para pasarlos a la vista.
     *
     * @return array{red: float, yellow: float, iva_rate: float, prices_include_iva: bool}
     */
    public function getConfig(): array
    {
        return [
            'red'               => $this->alertRed,
            'yellow'            => $this->alertYellow,
            'iva_rate'          => $this->ivaRate,
            'prices_include_iva'=> $this->pricesIncludeIva,
        ];
    }
}
