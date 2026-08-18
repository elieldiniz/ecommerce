<?php

namespace App\Actions\Refunds;

use App\Exceptions\Refunds\RefundNotAllowedException;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundReason;
use App\Models\User;
use App\Support\Safe2Pay\Safe2PayClient;
use Illuminate\Http\Client\ConnectionException;

class RequestCardRefund
{
    /**
     * Abre um estorno ativo de Cartão (RF-32), espelhando `RequestPixRefund`
     * (RF-12) integralmente: exige `$payment->status->slug === 'authorized'`
     * como pré-condição, cria a linha em `refunds` (T07) **antes** da chamada
     * síncrona a CT-03, e a confirmação definitiva ocorre apenas via webhook
     * de status 6 (Estornado) ou 13 (Chargeback, RF-15) — nunca por esta
     * action. Boleto não possui action equivalente nesta feature (Q-05).
     */
    public function execute(Payment $payment, RefundReason $reason, User $user, string $ipAddress): Refund
    {
        if ($payment->status->slug !== 'authorized') {
            throw new RefundNotAllowedException($payment);
        }

        $refund = (new CreateRefund)->execute($payment, $reason, $user, 'card_refund_requested', $ipAddress);

        try {
            $response = (new Safe2PayClient)->refundCard($payment->gateway_transaction_id);

            if ($response->failed()) {
                $this->recordSyncFailure($refund, $user, $ipAddress, $response->json() ?? ['status' => $response->status()]);
            }
        } catch (ConnectionException $exception) {
            $this->recordSyncFailure($refund, $user, $ipAddress, ['error' => $exception->getMessage()]);
        }

        return $refund;
    }

    /**
     * @param  array<mixed>  $errorBody
     */
    private function recordSyncFailure(Refund $refund, User $user, string $ipAddress, array $errorBody): void
    {
        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'card_refund_sync_call_failed',
            'entity' => 'refund',
            'entity_id' => $refund->id,
            'data_before' => null,
            'data_after' => $errorBody,
            'ip_address' => $ipAddress,
        ]);
    }
}
