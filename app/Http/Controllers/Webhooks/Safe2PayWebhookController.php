<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSafe2PayWebhookJob;
use App\Models\PaymentEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Safe2PayWebhookController extends Controller
{
    /**
     * Grava o payload bruto em `payment_events` antes de qualquer interpretação
     * (RF-19), mesmo quando `IdTransaction` não corresponde a nenhum `Payment`
     * conhecido. Idempotência por `event_hash` (sha256 do corpo bruto) garante
     * que reenvios nunca despachem um novo job de interpretação (RF-20).
     * Responde 200 imediatamente, sem aguardar o processamento assíncrono (RF-22).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        try {
            $payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json(['message' => 'Corpo da requisição inválido.'], 422);
        }

        $paymentEvent = PaymentEvent::query()->firstOrCreate(
            ['event_hash' => hash('sha256', $rawBody)],
            [
                'gateway_transaction_id' => (string) ($payload['IdTransaction'] ?? ''),
                'received_status' => (string) ($payload['TransactionStatus']['Code'] ?? ''),
                'payload' => $payload,
                'received_at' => now(),
            ],
        );

        if ($paymentEvent->wasRecentlyCreated) {
            ProcessSafe2PayWebhookJob::dispatch($paymentEvent);
        }

        return response()->json(['received' => true]);
    }
}
