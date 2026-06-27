<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor por Defecto de Inteligencia Artificial
    |--------------------------------------------------------------------------
    |
    | Aquí se define qué proveedor se utilizará para el AI Copilot.
    | Por defecto usamos DeepSeek V3 por su relación costo/beneficio en ERPs.
    |
    */

    'default' => env('AI_PROVIDER', 'deepseek'),

    'deepseek' => [
        'base_url'    => env('AI_DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
        'api_key'     => env('AI_DEEPSEEK_API_KEY'),
        'model'       => env('AI_DEEPSEEK_MODEL', 'deepseek-chat'),
        'max_tokens'  => (int) env('AI_DEEPSEEK_MAX_TOKENS', 1024),
        'temperature' => (float) env('AI_DEEPSEEK_TEMPERATURE', 0.3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Opciones de Fallback Semántico
    |--------------------------------------------------------------------------
    |
    | Umbral de confianza mínimo para aceptar un JSON de intención de la IA.
    | Si el modelo devuelve una confianza menor, se marcará como pendiente.
    |
    */
    'min_confidence' => 0.6,

];
