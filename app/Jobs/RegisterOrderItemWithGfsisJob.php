<?php

namespace App\Jobs;

use App\Exceptions\Gfsis\GfsisRegistrationBlockedException;
use App\Models\GfsisStatus;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Support\Gfsis\GfsisClient;
use App\Support\Gfsis\GfsisErrorCode;
use App\Support\Gfsis\GfsisPayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Consumidor do sinal `send_to_gfsis` (`integration_queue`) e do gatilho de
 * dados completos (RF-03). `handle()` sempre relê `$order` do banco (RF-04 —
 * nunca a partir do estado em memória do gatilho que originou o despacho) e só
 * chama `CriaPedidoVendaLTS` quando `orders.status = paid` e
 * `orders.fulfillment_status = data_complete`. O `gfsis_order_id` só é gerado
 * na 1ª execução; qualquer reexecução reaproveita o mesmo valor (RF-09).
 */
class RegisterOrderItemWithGfsisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->onQueue('database');
    }

    public function handle(): void
    {
        $order = $this->order->fresh(['status', 'fulfillmentStatus', 'items.productVariant', 'items.issuanceData']);

        if (! $order || $order->status->slug !== 'paid' || $order->fulfillmentStatus->slug !== 'data_complete') {
            return;
        }

        $orderItem = $order->items->first();

        if (! $orderItem) {
            return;
        }

        $orderItemGfsis = OrderItemGfsis::query()->firstOrCreate(
            ['order_item_id' => $orderItem->id],
            ['gfsis_order_id' => $this->generateUniqueGfsisOrderId()],
        );

        try {
            $this->ensureCertificateIsConfigured($orderItem);
        } catch (GfsisRegistrationBlockedException $exception) {
            $orderItemGfsis->update(['last_error' => $exception->getMessage()]);

            return;
        }

        $payload = (new GfsisPayloadBuilder)->build($orderItemGfsis);

        try {
            $response = (new GfsisClient)->criarPedidoVenda($payload);
            $body = $response->json() ?? [];
        } catch (Throwable $exception) {
            $this->applyFailure($order, $orderItemGfsis, $payload, null, $exception->getMessage());

            return;
        }

        $codigo = isset($body['codigo']) ? (string) $body['codigo'] : null;
        $isDuplicate = GfsisErrorCode::fromCode($codigo)?->isDuplicateSuccess() ?? false;
        $isSuccess = $response->successful() && ($body['erro'] ?? null) === false;

        if ($isSuccess || $isDuplicate) {
            $this->applySuccess($order, $orderItemGfsis, $payload, $body, $isDuplicate);

            return;
        }

        $message = GfsisErrorCode::fromCode($codigo)?->message() ?? 'Erro inesperado retornado pelo GFSIS.';

        $this->applyFailure($order, $orderItemGfsis, $payload, $body, $message);
    }

    /**
     * @throws GfsisRegistrationBlockedException quando a variante não tem `gfsis_certificado_id` configurado (RF-10)
     */
    private function ensureCertificateIsConfigured(OrderItem $orderItem): void
    {
        if ($orderItem->productVariant->gfsis_certificado_id === null) {
            throw new GfsisRegistrationBlockedException(
                "Variante {$orderItem->productVariant->sku} sem gfsis_certificado_id configurado."
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $body
     */
    private function applySuccess(Order $order, OrderItemGfsis $orderItemGfsis, array $payload, array $body, bool $isDuplicate): void
    {
        $orderItemGfsis->update([
            'gfsis_code' => $body['codigo'] ?? $orderItemGfsis->gfsis_code,
            'sent_at' => now(),
            'attempts' => $isDuplicate ? $orderItemGfsis->attempts : $orderItemGfsis->attempts + 1,
            'request_payload' => $payload,
            'response_payload' => $body,
            'status_id' => GfsisStatus::query()->where('slug', 'enviado_gfsis')->value('id'),
        ]);

        $order->update([
            'fulfillment_status_id' => OrderFulfillmentStatus::query()->where('slug', 'sent_to_gfsis')->value('id'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $body
     */
    private function applyFailure(Order $order, OrderItemGfsis $orderItemGfsis, array $payload, ?array $body, string $message): void
    {
        $orderItemGfsis->update([
            'last_error' => $message,
            'attempts' => $orderItemGfsis->attempts + 1,
            'request_payload' => $payload,
            'response_payload' => $body,
        ]);

        $order->update([
            'fulfillment_status_id' => OrderFulfillmentStatus::query()->where('slug', 'send_failed')->value('id'),
        ]);
    }

    private function generateUniqueGfsisOrderId(): int
    {
        do {
            $id = random_int(100000000, 999999999);
        } while (OrderItemGfsis::query()->where('gfsis_order_id', $id)->exists());

        return $id;
    }
}
