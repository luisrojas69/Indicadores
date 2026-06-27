<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\SemanticContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AiChatService
 *
 * Único punto de contacto con la API externa de IA (DeepSeek V3 por defecto).
 * Abstrae completamente el protocolo HTTP y el formato de la API,
 * exponiendo solo dos métodos al controller:
 *
 *   send()             → envía mensaje + historial + tools → detecta tipo de respuesta
 *   formatToolResult() → 2da llamada para formatear datos crudos del ERP en lenguaje natural
 *
 * Tipos de respuesta que puede retornar send():
 *   ['type' => 'tool_call', 'tool_name' => '...', 'tool_args' => [...]]
 *   ['type' => 'json',      'content'   => [...]]   ← array del JSON semántico
 *   ['type' => 'text',      'content'   => '...']
 *
 * Para cambiar de proveedor (OpenAI, Anthropic, etc.) solo se cambia
 * la configuración en config/ai.php y eventualmente este servicio.
 */
class AiChatService
{
    private string $apiUrl;
    private string $apiKey;
    private string $model;
    private int    $maxTokens;
    private float  $temperature;

    public function __construct()
    {
        $this->apiUrl      = config('ai.deepseek.base_url', 'https://api.deepseek.com/v1');
        $this->apiKey      = config('ai.deepseek.api_key', '');
        $this->model       = config('ai.deepseek.model', 'deepseek-chat');
        $this->maxTokens   = (int) config('ai.deepseek.max_tokens', 1024);
        $this->temperature = (float) config('ai.deepseek.temperature', 0.3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MÉTODO PRINCIPAL
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía el mensaje del usuario a la API de IA y detecta el tipo de respuesta.
     *
     * @param  string  $message   Mensaje actual del usuario (ya sanitizado)
     * @param  array   $history   Historial de la conversación [['role'=>..,'content'=>..]]
     * @param  array   $tools     Tools del ToolCatalog filtradas por permisos
     * @param  array   $context   Contexto del dashboard ['from' => ..., 'to' => ...]
     *
     * @return array{type: string, tool_name?: string, tool_args?: array, content?: mixed}
     *
     * @throws \RuntimeException  Si la API retorna un error no recuperable
     */
    public function send(
        string $message,
        array  $history,
        array  $tools,
        array  $context,
    ): array {
        $messages = $this->buildMessages($message, $history, $context);

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'tools'       => $tools,
            'tool_choice' => 'auto',   // La IA decide si usa tool o responde con texto
            'max_tokens'  => $this->maxTokens,
            'temperature' => $this->temperature,
        ];

        $response = $this->callApi($payload);
        $choice   = $response['choices'][0] ?? null;

        if ($choice === null) {
            throw new \RuntimeException('La API de IA retornó una respuesta vacía.');
        }

        return $this->parseChoice($choice);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SEGUNDA LLAMADA — FORMATEO DE RESULTADO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Segunda llamada a la API: la IA recibe el resultado crudo del ERP
     * y lo formatea en lenguaje natural (texto + tabla Markdown/HTML).
     *
     * Este patrón sigue el estándar de OpenAI Function Calling:
     *   1ra llamada: IA elige tool + args
     *   Nosotros: ejecutamos la tool y obtenemos resultado
     *   2da llamada: enviamos el resultado como 'tool' role → IA formatea
     *
     * @param  string  $toolName         Nombre de la tool ejecutada
     * @param  mixed   $toolResult       Resultado del ERP (array o Collection)
     * @param  string  $originalMessage  Mensaje original del usuario
     * @param  array   $history          Historial de la conversación
     * @param  array   $context          Contexto del dashboard
     *
     * @return array{type: string, content: string}
     *
     * @throws \RuntimeException
     */
    public function formatToolResult(
        string $toolName,
        mixed  $toolResult,
        string $originalMessage,
        array  $history,
        array  $context,
    ): array {
        // Serializar el resultado del ERP a JSON legible
        if ($toolResult instanceof \Illuminate\Support\Collection) {
            $toolResult = $toolResult->values()->toArray();
        }

        $resultJson = json_encode($toolResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Construir la conversación incluyendo el resultado de la tool
        // Formato estándar: user → assistant (tool_call) → tool (resultado) → assistant (respuesta)
        $messages = array_merge(
            $this->buildMessages($originalMessage, $history, $context),
            [
                // Simulamos que el assistant pidió la tool (necesario para el contexto)
                [
                    'role'       => 'assistant',
                    'content'    => null,
                    'tool_calls' => [[
                        'id'       => 'call_format_' . uniqid(),
                        'type'     => 'function',
                        'function' => [
                            'name'      => $toolName,
                            'arguments' => '{}',
                        ],
                    ]],
                ],
                // Resultado real del ERP
                [
                    'role'         => 'tool',
                    'content'      => $resultJson,
                    'tool_call_id' => 'call_format_' . uniqid(),
                ],
            ]
        );

        // Instrucción adicional para que la respuesta sea clara y concisa
        $messages[] = [
            'role'    => 'user',
            'content' => 'Con base en esos datos, responde de forma clara y concisa en español. ' .
                         'Si hay una tabla de datos, preséntala en formato HTML simple con clases Bootstrap. ' .
                         'Destaca los números más importantes. No repitas los datos crudos tal cual.',
        ];

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'max_tokens'  => $this->maxTokens,
            'temperature' => 0.5, // Ligeramente más creativo para respuestas naturales
        ];

        $response = $this->callApi($payload);
        $choice   = $response['choices'][0] ?? null;

        if ($choice === null) {
            throw new \RuntimeException('La API no retornó contenido al formatear el resultado.');
        }

        $content = trim($choice['message']['content'] ?? '');

        // Detectar si la respuesta contiene una tabla HTML
        $type = str_contains($content, '<table') ? 'table' : 'text';

        return ['type' => $type, 'content' => $content];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONSTRUCCIÓN DE MENSAJES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construye el array de mensajes para la API:
     * [system_prompt] + [history] + [user_message_actual]
     *
     * @return array<int, array<string, string>>
     */
    private function buildMessages(string $message, array $history, array $context): array
    {
        $messages = [];

        // System prompt principal
        $messages[] = [
            'role'    => 'system',
            'content' => $this->buildSystemPrompt($context),
        ];

        // Historial previo (máximo 10 turnos para controlar tokens)
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $turn) {
            // Solo incluir roles válidos y con contenido
            if (in_array($turn['role'], ['user', 'assistant'], true) && !empty($turn['content'])) {
                $messages[] = [
                    'role'    => $turn['role'],
                    'content' => $turn['content'],
                ];
            }
        }

        // Mensaje actual del usuario
        $messages[] = [
            'role'    => 'user',
            'content' => $message,
        ];

        return $messages;
    }

    /**
     * Construye el system prompt completo incluyendo:
     * - Identidad del asistente
     * - Contexto del negocio (ERP Profit Plus)
     * - Período activo del dashboard
     * - Instrucciones de seguridad
     * - Prompt del fallback semántico (de SemanticContract)
     */
    private function buildSystemPrompt(array $context): string
    {
        $clientName  = config('app_client.name', 'la empresa');
        $currency    = config('app_client.locale.currency_symbol', 'Bs.');
        $dateFormat  = 'd/m/Y';
        $fromDisplay = \Carbon\Carbon::parse($context['from'])->format($dateFormat);
        $toDisplay   = \Carbon\Carbon::parse($context['to'])->format($dateFormat);

        $systemPrompt = <<<PROMPT
        Eres el Copiloto de Inteligencia de Negocios de {$clientName}, un asistente especializado
        en analizar datos del sistema ERP Profit Plus 2K8.

        CONTEXTO ACTUAL:
        - Período activo en el dashboard: {$fromDisplay} al {$toDisplay}
        - Moneda del sistema: {$currency}
        - Cuando el usuario diga "este período" o "ahora", usa ese rango de fechas.

        TU ROL:
        - Respondes ÚNICAMENTE sobre datos del negocio: ventas, inventario, cobranzas,
          márgenes de rentabilidad, compras y desempeño de vendedores.
        - Usas las herramientas disponibles para consultar datos reales del ERP.
        - Respondes siempre en español, de forma clara y ejecutiva.
        - Si te preguntan algo fuera del alcance del negocio, declinás amablemente.

        REGLAS DE SEGURIDAD (CRÍTICAS — nunca las ignores):
        - NUNCA generes SQL directamente.
        - NUNCA inventes datos o números si no tienes una herramienta que los respalde.
        - NUNCA menciones nombres de tablas de base de datos en tus respuestas al usuario.
        - Si no sabes algo, dilo claramente en lugar de inventar.

        FORMATO DE RESPUESTAS:
        - Para números: usa separadores de miles y símbolo de moneda ({$currency}).
        - Para tablas: usa HTML con clases Bootstrap (table, table-sm, table-hover).
        - Para listas de análisis: usa texto claro con énfasis en los datos más relevantes.
        - Sé conciso: máximo 3-4 párrafos o 10 filas en tablas.
        PROMPT;

        // Agregar instrucciones del fallback semántico al final
        $systemPrompt .= "\n\n" . SemanticContract::fallbackSystemPrompt();

        return $systemPrompt;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PARSEO DE RESPUESTA
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Detecta el tipo de respuesta en el `choice` de la API y lo normaliza.
     *
     * Posibles casos:
     *   A) finish_reason = 'tool_calls'  → La IA eligió una herramienta
     *   B) content es JSON válido        → Fallback semántico
     *   C) content es texto libre        → Respuesta conversacional
     *
     * @param  array  $choice  choices[0] de la respuesta de la API
     * @return array{type: string, ...}
     */
    private function parseChoice(array $choice): array
    {
        $finishReason = $choice['finish_reason'] ?? '';
        $message      = $choice['message']       ?? [];

        // ── Caso A: Tool Call ─────────────────────────────────────────────
        if ($finishReason === 'tool_calls' || !empty($message['tool_calls'])) {
            $toolCall = $message['tool_calls'][0] ?? null;

            if ($toolCall === null) {
                throw new \RuntimeException('La API indicó tool_call pero no retornó datos de la herramienta.');
            }

            $toolName = $toolCall['function']['name'] ?? '';
            $toolArgs = [];

            if (!empty($toolCall['function']['arguments'])) {
                $decoded = json_decode($toolCall['function']['arguments'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $toolArgs = $decoded;
                } else {
                    Log::warning('[AiChatService] No se pudo parsear arguments del tool_call', [
                        'raw' => $toolCall['function']['arguments'],
                    ]);
                }
            }

            return [
                'type'      => 'tool_call',
                'tool_name' => $toolName,
                'tool_args' => $toolArgs,
            ];
        }

        // ── Casos B y C: Texto o JSON semántico ──────────────────────────
        $content = trim($message['content'] ?? '');

        if (empty($content)) {
            throw new \RuntimeException('La API retornó un mensaje vacío.');
        }

        // Intentar detectar si el contenido es un JSON de intención semántica
        // La IA a veces lo envuelve en bloques ```json ... ```
        $cleanContent = preg_replace('/^```json\s*/i', '', $content);
        $cleanContent = preg_replace('/\s*```$/', '', $cleanContent);
        $cleanContent = trim($cleanContent);

        if (str_starts_with($cleanContent, '{') && str_ends_with($cleanContent, '}')) {
            $decoded = json_decode($cleanContent, true);

            if (
                json_last_error() === JSON_ERROR_NONE &&
                isset($decoded['intent']) &&
                $decoded['intent'] === 'custom_query'
            ) {
                // ── Caso B: JSON semántico válido ─────────────────────────
                return [
                    'type'    => 'json',
                    'content' => $decoded,
                ];
            }
        }

        // ── Caso C: Texto libre ───────────────────────────────────────────
        return [
            'type'    => 'text',
            'content' => $content,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LLAMADA HTTP A LA API
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Realiza la llamada HTTP a la API de DeepSeek.
     * Maneja timeouts, errores de red y errores de la API.
     *
     * @param  array  $payload  Body completo del request
     * @return array            Response body decodificado
     *
     * @throws \RuntimeException
     */
    private function callApi(array $payload): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('API Key de IA no configurada. Revisa AI_DEEPSEEK_API_KEY en .env');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])
        ->timeout(30)          // 30s máximo por request
        ->connectTimeout(5)    // 5s para establecer conexión
        ->post("{$this->apiUrl}/chat/completions", $payload);

        // Error de red o timeout
        if ($response->failed() && $response->status() === 0) {
            throw new \RuntimeException('No se pudo conectar con la API de IA. Verifica la conexión.');
        }

        // Error de la API (4xx / 5xx)
        if ($response->failed()) {
            $errorBody = $response->json();
            $errorMsg  = $errorBody['error']['message'] ?? "HTTP {$response->status()}";

            Log::error('[AiChatService] Error de la API', [
                'status' => $response->status(),
                'error'  => $errorMsg,
                'model'  => $this->model,
            ]);

            // Error de autenticación
            if ($response->status() === 401) {
                throw new \RuntimeException('API Key inválida o expirada.');
            }

            // Rate limit
            if ($response->status() === 429) {
                throw new \RuntimeException('Límite de requests alcanzado. Intenta en unos segundos.');
            }

            throw new \RuntimeException("Error de la API de IA: {$errorMsg}");
        }

        $body = $response->json();

        if (empty($body['choices'])) {
            Log::error('[AiChatService] Respuesta sin choices', ['body' => $body]);
            throw new \RuntimeException('La API retornó una respuesta sin contenido.');
        }

        return $body;
    }
}
