<?php

namespace App\Actions\Payments;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePaymentStatusManually
{
    /**
     * Aplica uma alteração manual de `payments.status_id` (fora do fluxo de
     * webhook) e cria a linha de auditoria correspondente na mesma
     * transação (RF-29); reutilizável por uma futura tela de painel.
     */
    public function execute(Payment $payment, PaymentStatus $newStatus, User $user): Payment
    {
        return DB::transaction(function () use ($payment, $newStatus, $user) {
            $dataBefore = ['status_id' => $payment->status_id];

            $payment->update(['status_id' => $newStatus->id]);

            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => 'manual_status_change',
                'entity' => 'payment',
                'entity_id' => $payment->id,
                'data_before' => $dataBefore,
                'data_after' => ['status_id' => $newStatus->id],
                'ip_address' => request()->ip() ?? '127.0.0.1',
            ]);

            return $payment;
        });
    }
}
