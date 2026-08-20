<?php

namespace App\Actions\Payments;

use App\Actions\Checkout\RecalculateOrderTotals;
use App\Exceptions\Payments\InstallmentLimitExceededException;
use App\Exceptions\Payments\MissingVisitorIdException;
use App\Exceptions\Payments\PaymentTotalMismatchException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentStatus;
use App\Support\Safe2Pay\CardBrand;
use App\Support\Safe2Pay\PaymentPayloadBuilder;
use App\Support\Safe2Pay\Safe2PayClient;
use App\Support\Safe2Pay\TransactionStatus;

class ChargeCardPayment
{
    /**
     * Cobra via Cartão de Crédito (`PaymentMethod = "2"`) exclusivamente com
     * `Token` do Cofre de Chaves — nunca `CardNumber`/`SecurityCode`/`ExpirationDate`
     * (RF-13, RNF-01). Rejeita parcelas acima de `max_installments` e `VisitorID`
     * vazio antes de qualquer chamada HTTP (RF-14, RF-31). `gross_amount`,
     * `gateway_fee`, `net_amount` e `expected_settlement_date` de liquidação
     * nunca são gravados por esta action (RF-30, fora de escopo).
     */
    public function execute(Order $order, string $frontTotal, string $token, int $installments, string $visitorId): Payment
    {
        $totals = (new RecalculateOrderTotals)->execute(
            $order->items->map(fn ($item) => ['unit_price' => (string) $item->unit_price, 'quantity' => $item->quantity]),
            $order->paymentMethod,
            $order->coupon,
        );

        if (bccomp($totals['total'], $frontTotal, 2) !== 0) {
            throw new PaymentTotalMismatchException($totals['total'], $frontTotal);
        }

        if ($installments > $order->paymentMethod->max_installments) {
            throw new InstallmentLimitExceededException($installments, $order->paymentMethod->max_installments);
        }

        if (trim($visitorId) === '') {
            throw new MissingVisitorIdException;
        }

        $payload = (new PaymentPayloadBuilder)->base($order);
        $payload['PaymentObject'] = [
            'Token' => $token,
            'InstallmentQuantity' => $installments,
        ];
        $payload['VisitorID'] = $visitorId;
        $payload['ShouldUseAntiFraud'] = true;

        $response = (new Safe2PayClient)->charge($payload);
        $response->throw();

        $statusSlug = TransactionStatus::fromCode((int) $response->json('ResponseDetail.Status'))->toInternalStatusSlug();

        $cardNumber = (string) $response->json('ResponseDetail.CreditCard.CardNumber');
        $brandCode = (int) $response->json('ResponseDetail.CreditCard.Brand');

        return Payment::query()->create([
            'order_id' => $order->id,
            'payment_gateway_id' => PaymentGateway::query()->where('slug', 'safe2pay')->value('id'),
            'payment_method_id' => $order->payment_method_id,
            'status_id' => PaymentStatus::query()->where('slug', $statusSlug)->value('id'),
            'gateway_transaction_id' => (string) $response->json('ResponseDetail.IdTransaction'),
            'gross_amount' => $totals['total'],
            'installments' => $response->json('ResponseDetail.CreditCard.Installments') ?? $installments,
            'card_brand' => CardBrand::tryFrom($brandCode)?->label() ?? (string) $brandCode,
            'card_last_digits' => substr($cardNumber, -4),
            'authorization_nsu' => $response->json('ResponseDetail.Tid'),
        ]);
    }
}
