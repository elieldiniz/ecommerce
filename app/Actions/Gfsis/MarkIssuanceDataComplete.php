<?php

namespace App\Actions\Gfsis;

use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;

/**
 * RF-03: quando todos os campos obrigatórios de `issuance_data` (conforme
 * `holder_type`, mesma lista condicional de `StoreIssuanceDataRequest`) estão
 * preenchidos, grava `filled_at`, move `orders.fulfillment_status_id` para
 * `data_complete` e — lendo `orders.status_id` fresco do banco, nunca do
 * estado em memória do gatilho que originou a chamada (RF-04) — despacha
 * `RegisterOrderItemWithGfsisJob` apenas quando o pedido já está `paid`.
 */
class MarkIssuanceDataComplete
{
    /**
     * @var array<int, string>
     */
    private const PF_REQUIRED_FIELDS = [
        'holder_name', 'document', 'email', 'phone',
        'postal_code', 'street', 'number', 'neighborhood', 'city', 'state',
    ];

    /**
     * @var array<int, string>
     */
    private const PJ_REQUIRED_FIELDS = [
        'responsible_name', 'responsible_document', 'responsible_email', 'responsible_phone',
    ];

    public function execute(IssuanceData $issuanceData): void
    {
        if (! $this->isComplete($issuanceData)) {
            return;
        }

        $issuanceData->update(['filled_at' => now()]);

        $orderId = $issuanceData->orderItem->order_id;

        Order::query()->whereKey($orderId)->update([
            'fulfillment_status_id' => OrderFulfillmentStatus::query()->where('slug', 'data_complete')->value('id'),
        ]);

        $order = Order::query()->with('status')->findOrFail($orderId);

        if ($order->status->slug === 'paid') {
            RegisterOrderItemWithGfsisJob::dispatch($order);
        }
    }

    private function isComplete(IssuanceData $issuanceData): bool
    {
        $requiredFields = self::PF_REQUIRED_FIELDS;

        if ($issuanceData->holderType->slug === 'pj') {
            $requiredFields = [...$requiredFields, ...self::PJ_REQUIRED_FIELDS];
        }

        foreach ($requiredFields as $field) {
            if (blank($issuanceData->{$field})) {
                return false;
            }
        }

        return true;
    }
}
