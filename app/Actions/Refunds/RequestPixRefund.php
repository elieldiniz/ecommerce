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

class RequestPixRefund
{
    /**
     * Abre um estorno ativo de Pix (RF-12). Exige `$payment->status->slug ===
     * 'authorized'` como pré-condição, sem a qual nenhuma linha é criada em
     * `refunds`. A linha em `refunds` é criada (T07) **antes** da chamada
     * síncrona a CT-02; a confirmação definitiva de `payments.status_id =
     * reversed` só ocorre via webhook de status 6 (T18/T20), nunca por esta
     * action. Se a chamada síncrona falhar (HTTP não-2xx ou exceção de
     * conexão), cria uma linha adicional em `audit_logs` referenciando o
     * refund já criado, sem deixá-lo órfão e silencioso.
     */
    public function execute(Payment $payment, RefundReason $reason, User $user, string $ipAddress): Refund
    {
        if ($payment->status->slug !== 'authorized') {
            throw new RefundNotAllowedException($payment);
        }

        $refund = (new CreateRefund)->execute($payment, $reason, $user, 'pix_refund_requested', $ipAddress);

        try {
            $response = (new Safe2PayClient)->refundPix($payment->gateway_transaction_id);

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
            'action' => 'pix_refund_sync_call_failed',
            'entity' => 'refund',
            'entity_id' => $refund->id,
            'data_before' => null,
            'data_after' => $errorBody,
            'ip_address' => $ipAddress,
        ]);
    }
}
