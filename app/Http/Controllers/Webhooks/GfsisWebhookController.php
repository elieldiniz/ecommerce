<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessGfsisWebhookJob;
use App\Models\GfsisEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GfsisWebhookController extends Controller
{
    /**
     * Grava o payload bruto em `gfsis_events` antes de qualquer interpretação
     * (RF-11), incondicionalmente — inclusive quando `identificador` não
     * corresponde a nenhum `order_item_gfsis` conhecido ou está ausente do
     * payload (a migration que remove a FK e torna a coluna nullable já foi
     * aplicada). Idempotência por `event_hash` (sha256 do corpo bruto)
     * garante que reenvios nunca despachem um novo job de processamento.
     * Responde 200 imediatamente, sem aguardar o processamento assíncrono
     * (RNF-04).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        try {
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json(['message' => 'Corpo da requisição inválido.'], 422);
        }

        $identificador = array_key_exists('identificador', $payload) ? (int) $payload['identificador'] : null;

        $gfsisEvent = GfsisEvent::query()->firstOrCreate(
            ['event_hash' => hash('sha256', $rawBody)],
            [
                'gfsis_order_id' => $identificador,
                'received_status' => (string) ($payload['status'] ?? ''),
                'payload' => $payload,
                'received_at' => now(),
            ],
        );

        if ($gfsisEvent->wasRecentlyCreated) {
            ProcessGfsisWebhookJob::dispatch($gfsisEvent);
        }

        return response()->json(['received' => true]);
    }
}
