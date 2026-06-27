<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Ai\SemanticContract;
use App\Ai\ToolCatalog;
use App\Erp\Contracts\ErpConnectionInterface;
use App\Http\Controllers\Controller;
use App\Services\Ai\AiChatService;
use App\Services\Ai\ToolDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AiChatController
 *
 * Orquesta el flujo completo del AI Copilot:
 *
 *   1. Recibe el mensaje del usuario + historial de conversación
 *   2. Llama a AiChatService para enviar a DeepSeek V3
 *   3. Detecta el tipo de respuesta:
 *      a) tool_call    → ToolDispatcher ejecuta el método del ERP → AiChatService formatea
 *      b) text + JSON  → SemanticContract valida → responde con intent_pending o resuelve
 *      c) text libre   → retorna directamente
 *   4. Retorna siempre un JSON uniforme al frontend jQuery
 *
 * Endpoint: POST /api/ai/chat
 * Middleware: auth, throttle:30,1 (30 req/min por usuario)
 */
class AiChatController extends Controller
{
    public function __construct(
        private readonly AiChatService       $aiChat,
        private readonly ToolDispatcher      $dispatcher,
        private readonly ErpConnectionInterface $erp,
    ) {}

    /**
     * Punto de entrada principal del copiloto.
     *
     * Body esperado:
     * {
     *   "message": "¿Cuánto facturamos este mes?",
     *   "history": [
     *     {"role": "user",      "content": "Hola"},
     *     {"role": "assistant", "content": "¡Hola! ¿En qué te ayudo?"}
     *   ],
     *   "context": {
     *     "from": "2026-06-01",
     *     "to":   "2026-06-26"
     *   }
     * }
     *
     * Respuesta uniforme:
     * {
     *   "type":       "text|table|intent_pending|error",
     *   "content":    "...",
     *   "tool_used":  "get_dashboard_kpis|null",
     *   "confidence": 1.0,
     *   "meta":       {}
     * }
     */
    public function chat(Request $request): JsonResponse
    {
        // ── 1. Validar input ─────────────────────────────────────────────────
        $validated = $request->validate([
            'message'         => ['required', 'string', 'min:2', 'max:500'],
            'history'         => ['sometimes', 'array', 'max:20'],
            'history.*.role'  => ['required_with:history', 'in:user,assistant,tool'],
            'history.*.content' => ['required_with:history', 'string', 'max:2000'],
            'context'         => ['sometimes', 'array'],
            'context.from'    => ['sometimes', 'date_format:Y-m-d'],
            'context.to'      => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $user    = $request->user();
        $message = strip_tags(trim($validated['message']));
        $history = $validated['history'] ?? [];
        $context = $validated['context'] ?? [
            'from' => now()->startOfMonth()->toDateString(),
            'to'   => now()->toDateString(),
        ];

        // ── 2. Obtener tools permitidas según permisos del usuario ───────────
        $tools = ToolCatalog::forUser($user);

        if (empty($tools)) {
            return $this->respond(
                type:    'error',
                content: 'No tienes permisos para usar el Copiloto. Contacta al administrador.',
            );
        }

        // ── 3. Enviar a DeepSeek V3 ──────────────────────────────────────────
        try {
            $aiResponse = $this->aiChat->send(
                message: $message,
                history: $history,
                tools:   $tools,
                context: $context,
            );
        } catch (\Throwable $e) {
            Log::error('[AiCopilot] Error al llamar a la API de IA', [
                'user_id' => $user->id,
                'message' => $message,
                'error'   => $e->getMessage(),
            ]);

            return $this->respond(
                type:    'error',
                content: 'El servicio de IA no está disponible en este momento. Intenta de nuevo.',
            );
        }

        // ── 4. Procesar la respuesta según su tipo ───────────────────────────
        return match ($aiResponse['type']) {
            'tool_call' => $this->handleToolCall($aiResponse, $message, $history, $context, $user),
            'json'      => $this->handleSemanticIntent($aiResponse, $context),
            'text'      => $this->handleText($aiResponse),
            default     => $this->respond(type: 'error', content: 'Respuesta inesperada del modelo.'),
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HANDLERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * NIVEL 1 — Tool Call.
     * La IA eligió una herramienta. ToolDispatcher la ejecuta de forma segura,
     * luego se hace una 2da llamada a la IA para que formatee el resultado.
     */
    private function handleToolCall(
        array $aiResponse,
        string $originalMessage,
        array $history,
        array $context,
        \App\Models\User $user,
    ): JsonResponse {
        $toolName = $aiResponse['tool_name'];
        $toolArgs = $aiResponse['tool_args'];

        // Verificar que la herramienta esté en el catálogo Y permitida para este usuario
        $allowedTools = array_map(
            fn ($t) => $t['function']['name'],
            ToolCatalog::forUser($user)
        );

        if (!in_array($toolName, $allowedTools, true)) {
            Log::warning('[AiCopilot] Tool no permitida para el usuario', [
                'user_id'   => $user->id,
                'tool_name' => $toolName,
            ]);

            return $this->respond(
                type:    'error',
                content: 'No tienes permiso para ejecutar esa consulta.',
            );
        }

        // Ejecutar la herramienta contra el ERP
        try {
            $rawData = $this->dispatcher->execute($toolName, $toolArgs);
        } catch (\Throwable $e) {
            Log::error('[AiCopilot] Error en ToolDispatcher', [
                'tool'  => $toolName,
                'args'  => $toolArgs,
                'error' => $e->getMessage(),
            ]);

            return $this->respond(
                type:    'error',
                content: 'No se pudo ejecutar la consulta al ERP. Verifica la conexión.',
                meta:    ['tool_attempted' => $toolName],
            );
        }

        // Segunda llamada a la IA para que formatee el resultado en lenguaje natural
        try {
            $formatted = $this->aiChat->formatToolResult(
                toolName:        $toolName,
                toolResult:      $rawData,
                originalMessage: $originalMessage,
                history:         $history,
                context:         $context,
            );
        } catch (\Throwable $e) {
            // Si la 2da llamada falla, retornar los datos crudos como tabla
            Log::warning('[AiCopilot] Error al formatear resultado de tool', [
                'tool'  => $toolName,
                'error' => $e->getMessage(),
            ]);

            return $this->respond(
                type:      'table',
                content:   $this->arrayToHtmlTable($rawData),
                toolUsed:  $toolName,
                confidence: 1.0,
                meta:      ['raw_fallback' => true],
            );
        }

        return $this->respond(
            type:       $formatted['type'] ?? 'text',
            content:    $formatted['content'],
            toolUsed:   $toolName,
            confidence: 1.0,
        );
    }

    /**
     * NIVEL 2 — Fallback Semántico.
     * La IA generó un JSON de intención. SemanticContract lo valida.
     * Si hay un tool que lo resuelva, se ejecuta. Si no, se informa al usuario.
     */
    private function handleSemanticIntent(array $aiResponse, array $context): JsonResponse
    {
        $intent = $aiResponse['content']; // array ya parseado por AiChatService

        $validation = SemanticContract::validate($intent);

        if (!$validation['valid']) {
            Log::warning('[AiCopilot] JSON semántico inválido', [
                'intent' => $intent,
                'errors' => $validation['errors'],
            ]);

            return $this->respond(
                type:    'intent_pending',
                content: 'No pude entender completamente tu consulta. ¿Puedes reformularla? ' .
                         'Por ejemplo: "¿Cuánto facturamos este mes?" o "¿Qué productos tienen stock crítico?"',
                meta:    ['validation_errors' => $validation['errors']],
            );
        }

        // ¿Hay un tool que resuelva esta intención?
        $tool = $validation['tool'];

        if ($tool === null) {
            // Combinación válida en estructura pero sin implementación aún
            $entityLabel = SemanticContract::ENTITIES[$intent['entity']] ?? $intent['entity'];

            return $this->respond(
                type:    'intent_pending',
                content: "Entiendo que preguntas sobre **{$entityLabel}**. " .
                         "Esta consulta específica aún no está implementada en el Copiloto, " .
                         "pero está en nuestra hoja de ruta. Por ahora puedes consultar: " .
                         "ventas del período, ranking de vendedores, stock crítico, " .
                         "cuentas por cobrar y márgenes de rentabilidad.",
                meta:    [
                    'intent_received' => $intent,
                    'entity'          => $intent['entity'],
                    'metrics'         => $intent['metrics'],
                ],
            );
        }

        // Resolver el contexto de fechas desde los filtros del intent
        $resolvedContext = $this->resolveContextFromIntent($intent, $context);

        // Ejecutar el tool que resuelve la intención
        try {
            $rawData = $this->dispatcher->execute($tool, [
                'date_from' => $resolvedContext['from'],
                'date_to'   => $resolvedContext['to'],
            ]);
        } catch (\Throwable $e) {
            return $this->respond(
                type:    'error',
                content: 'Error al consultar los datos del ERP.',
                meta:    ['tool' => $tool, 'error' => $e->getMessage()],
            );
        }

        return $this->respond(
            type:       'table',
            content:    $this->arrayToHtmlTable($rawData),
            toolUsed:   $tool,
            confidence: (float)($intent['confidence'] ?? 0.8),
            meta:       ['resolved_from_intent' => true, 'intent' => $intent],
        );
    }

    /**
     * Texto libre — la IA respondió directamente sin tools ni JSON.
     * Ocurre en saludos, preguntas fuera de alcance o respuestas explicativas.
     */
    private function handleText(array $aiResponse): JsonResponse
    {
        return $this->respond(
            type:    'text',
            content: $aiResponse['content'],
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Respuesta JSON uniforme para el frontend.
     */
    private function respond(
        string  $type,
        string  $content,
        ?string $toolUsed   = null,
        float   $confidence = 1.0,
        array   $meta       = [],
    ): JsonResponse {
        return response()->json([
            'type'       => $type,
            'content'    => $content,
            'tool_used'  => $toolUsed,
            'confidence' => $confidence,
            'meta'       => $meta,
        ]);
    }

    /**
     * Convierte un array de datos del ERP a una tabla HTML con clases Bootstrap.
     * Usado como fallback cuando la 2da llamada a la IA falla.
     *
     * @param  array|iterable  $data
     */
    private function arrayToHtmlTable(mixed $data): string
    {
        // Normalizar: Collection → array, array asociativo → array de un elemento
        if ($data instanceof \Illuminate\Support\Collection) {
            $data = $data->values()->toArray();
        } elseif (is_array($data) && !isset($data[0])) {
            $data = [$data]; // Array asociativo plano → wrappear en array
        }

        if (empty($data)) {
            return '<p class="text-muted mb-0"><i class="fas fa-inbox me-1"></i>Sin datos para mostrar.</p>';
        }

        $headers = array_keys((array)$data[0]);

        $thead = '<thead><tr>' .
            implode('', array_map(
                fn ($h) => '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $h))) . '</th>',
                $headers
            )) .
            '</tr></thead>';

        $tbody = '<tbody>' .
            implode('', array_map(function ($row) use ($headers) {
                $row = (array)$row;
                $cells = implode('', array_map(
                    fn ($h) => '<td>' . htmlspecialchars((string)($row[$h] ?? '—')) . '</td>',
                    $headers
                ));
                return "<tr>{$cells}</tr>";
            }, $data)) .
            '</tbody>';

        return '<div class="table-responsive">' .
               '<table class="table table-sm table-hover ai-result-table mb-0">' .
               $thead . $tbody .
               '</table></div>';
    }

    /**
     * Resuelve las fechas del contexto activo a partir de los filtros del intent semántico.
     * Convierte valores relativos ("this_month", "today") a fechas absolutas.
     *
     * @param  array  $intent   JSON de intención validado
     * @param  array  $context  Contexto de fechas del dashboard (from/to actuales)
     * @return array{from: string, to: string}
     */
    private function resolveContextFromIntent(array $intent, array $context): array
    {
        $filters = $intent['filters'] ?? [];
        $dateKey = $filters['date'] ?? null;

        // Si el intent trae fechas absolutas, usarlas directamente
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            return [
                'from' => $filters['date_from'],
                'to'   => $filters['date_to'],
            ];
        }

        // Resolver fechas relativas
        return match ($dateKey) {
            'today'        => ['from' => now()->toDateString(),                      'to' => now()->toDateString()],
            'yesterday'    => ['from' => now()->subDay()->toDateString(),             'to' => now()->subDay()->toDateString()],
            'this_week'    => ['from' => now()->startOfWeek()->toDateString(),        'to' => now()->toDateString()],
            'last_week'    => ['from' => now()->subWeek()->startOfWeek()->toDateString(), 'to' => now()->subWeek()->endOfWeek()->toDateString()],
            'this_month'   => ['from' => now()->startOfMonth()->toDateString(),       'to' => now()->toDateString()],
            'last_month'   => ['from' => now()->subMonth()->startOfMonth()->toDateString(), 'to' => now()->subMonth()->endOfMonth()->toDateString()],
            'this_quarter' => ['from' => now()->startOfQuarter()->toDateString(),     'to' => now()->toDateString()],
            'this_year'    => ['from' => now()->startOfYear()->toDateString(),        'to' => now()->toDateString()],
            // Si no hay filtro de fecha en el intent, usar el contexto actual del dashboard
            default        => ['from' => $context['from'], 'to' => $context['to']],
        };
    }
}
