<?php

namespace Tests\Unit\Actions\Refunds;

use App\Actions\Refunds\CreateRefund;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundReason;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_withdrawal_right_reason_marks_the_refund_as_requiring_revocation(): void
    {
        $payment = Payment::factory()->create();
        $reason = RefundReason::query()->firstOrCreate(['slug' => 'withdrawal_right'], ['name' => 'Arrependimento']);
        $user = User::factory()->create();

        $refund = (new CreateRefund)->execute($payment, $reason, $user, 'refund_requested', '127.0.0.1');

        $this->assertTrue($refund->requires_revocation);
    }

    public function test_any_other_reason_does_not_require_revocation(): void
    {
        $payment = Payment::factory()->create();
        $reason = RefundReason::query()->firstOrCreate(['slug' => 'duplicate'], ['name' => 'Duplicidade']);
        $user = User::factory()->create();

        $refund = (new CreateRefund)->execute($payment, $reason, $user, 'refund_requested', '127.0.0.1');

        $this->assertFalse($refund->requires_revocation);
    }

    public function test_it_creates_exactly_one_refund_and_one_audit_log_with_the_same_entity_id(): void
    {
        $payment = Payment::factory()->create();
        $reason = RefundReason::factory()->create();
        $user = User::factory()->create();

        $refund = (new CreateRefund)->execute($payment, $reason, $user, 'refund_requested', '127.0.0.1');

        $this->assertSame(1, Refund::query()->count());
        $this->assertSame(1, AuditLog::query()->count());

        $auditLog = AuditLog::query()->first();
        $this->assertSame('refund', $auditLog->entity);
        $this->assertSame($refund->id, $auditLog->entity_id);
        $this->assertSame($user->id, $auditLog->user_id);
    }

    public function test_it_rolls_back_the_refund_when_the_audit_log_fails_to_persist_in_the_same_transaction(): void
    {
        $payment = Payment::factory()->create();
        $reason = RefundReason::factory()->create();
        $user = User::factory()->create();

        try {
            (new CreateRefund)->execute($payment, $reason, $user, 'refund_requested', str_repeat('1', 46));
            $this->fail('Esperava-se uma QueryException por causa do ip_address excedendo 45 caracteres.');
        } catch (QueryException) {
            // esperado — coluna audit_logs.ip_address tem no máximo 45 caracteres
        }

        $this->assertSame(0, Refund::query()->count());
        $this->assertSame(0, AuditLog::query()->count());
    }
}
