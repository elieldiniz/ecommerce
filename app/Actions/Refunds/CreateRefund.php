<?php

namespace App\Actions\Refunds;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundReason;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRefund
{
    /**
     * Cria a linha de reembolso e a linha de auditoria correspondente na mesma transação (RF-26, RF-27).
     */
    public function execute(
        Payment $payment,
        RefundReason $reason,
        User $user,
        string $action,
        string $ipAddress,
    ): Refund {
        return DB::transaction(function () use ($payment, $reason, $user, $action, $ipAddress) {
            $refund = Refund::query()->create([
                'payment_id' => $payment->id,
                'reason_id' => $reason->id,
                'user_id' => $user->id,
                'amount' => $payment->gross_amount,
                'requires_revocation' => $reason->slug === 'withdrawal_right',
                'requested_at' => now(),
            ]);

            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => $action,
                'entity' => 'refund',
                'entity_id' => $refund->id,
                'data_before' => null,
                'data_after' => $refund->toArray(),
                'ip_address' => $ipAddress,
            ]);

            return $refund;
        });
    }
}
