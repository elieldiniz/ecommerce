<?php

namespace App\Actions\Payments;

use App\Actions\Checkout\RecalculateOrderTotals;
use App\Exceptions\Payments\PaymentTotalMismatchException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentStatus;
use App\Support\Safe2Pay\PaymentPayloadBuilder;
use App\Support\Safe2Pay\Safe2PayClient;

class ChargeBoletoPayment
{
    /**
     * Prazo de vencimento do boleto em dias corridos a partir da emissão —
     * constante documentada, não modelada por nenhuma RF específica (Seção 8.1).
     */
    private const DUE_DAYS = 3;

    /**
     * Cobra via Boleto (`PaymentMethod = "1"`) com o payload de exemplo da
     * Seção 8.1. Mesmo guard de divergência de `ChargePixPayment`/`ChargeCardPayment`
     * (RF-28). Em resposta, grava a linha digitável e a URL do comprovante (RF-16).
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

        $payload = (new PaymentPayloadBuilder)->base($order);
        $payload['PaymentObject'] = [
            'DueDate' => now()->addDays(self::DUE_DAYS)->format('d/m/Y'),
            'Instruction' => 'Não receber após o vencimento',
            'CancelAfterDue' => false,
            'DaysBeforeCancel' => 10,
            'PenaltyRate' => 2,
            'InterestRate' => 1,
            'IsEnablePartialPayment' => false,
        ];

        $response = (new Safe2PayClient)->charge($payload);
        $response->throw();

        return Payment::query()->create([
            'order_id' => $order->id,
            'payment_gateway_id' => PaymentGateway::query()->where('slug', 'safe2pay')->value('id'),
            'payment_method_id' => $order->payment_method_id,
            'status_id' => PaymentStatus::query()->where('slug', 'pending')->value('id'),
            'gateway_transaction_id' => (string) $response->json('ResponseDetail.IdTransaction'),
            'gross_amount' => $totals['total'],
            'boleto_digitable_line' => $response->json('ResponseDetail.DigitableLine'),
            'receipt_url' => $response->json('ResponseDetail.BankSlipUrl'),
        ]);
    }
}
