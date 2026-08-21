<?php

namespace App\Actions\Payments;

use App\Actions\Checkout\RecalculateOrderTotals;
use App\Exceptions\Payments\PaymentTotalMismatchException;
use App\Exceptions\Payments\Safe2PayChargeFailedException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentStatus;
use App\Models\Setting;
use App\Support\Safe2Pay\PaymentPayloadBuilder;
use App\Support\Safe2Pay\Safe2PayClient;

class ChargePixPayment
{
    /**
     * Cobra via Pix Dinâmico (`POST /v2/payment`, `PaymentMethod = "6"`), nunca
     * `/v2/staticPix` (RF-06). Recalcula os totais e bloqueia antes de qualquer
     * chamada HTTP se `$frontTotal` divergir (RF-28). Cada chamada gera uma nova
     * linha em `payments` com novo `gateway_transaction_id`, nunca reaproveitando
     * o QR de uma tentativa anterior (RF-11, RF-25). `pix_id` fica `null` — o
     * endpoint de criação não retorna TXID.
     */
    public function execute(Order $order, string $frontTotal): Payment
    {
        $totals = (new RecalculateOrderTotals)->execute(
            $order->items->map(fn ($item) => ['unit_price' => (string) $item->unit_price, 'quantity' => $item->quantity]),
            $order->paymentMethod,
            $order->coupon,
        );

        if (bccomp($totals['total'], $frontTotal, 2) !== 0) {
            throw new PaymentTotalMismatchException($totals['total'], $frontTotal);
        }

        $expirationSeconds = (int) Setting::query()->where('key', 'pix_expiration_seconds')->value('value');

        $payload = (new PaymentPayloadBuilder)->base($order);
        $payload['PaymentObject'] = ['Expiration' => $expirationSeconds];

        $response = (new Safe2PayClient)->charge($payload);
        $response->throw();

        if ($response->json('HasError') === true) {
            throw new Safe2PayChargeFailedException((array) $response->json());
        }

        return Payment::query()->create([
            'order_id' => $order->id,
            'payment_gateway_id' => PaymentGateway::query()->where('slug', 'safe2pay')->value('id'),
            'payment_method_id' => $order->payment_method_id,
            'status_id' => PaymentStatus::query()->where('slug', 'pending')->value('id'),
            'gateway_transaction_id' => (string) $response->json('ResponseDetail.IdTransaction'),
            'gross_amount' => $totals['total'],
            // TXID não é retornado por este endpoint de criação — sem fonte confirmada hoje.
            'pix_id' => null,
            'qr_code_payload' => $response->json('ResponseDetail.Key'),
            'qr_code_image_url' => $response->json('ResponseDetail.QrCode'),
            'expires_at' => now()->addSeconds($expirationSeconds),
        ]);
    }
}
