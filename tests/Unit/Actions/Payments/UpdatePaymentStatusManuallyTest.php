<?php

namespace Tests\Unit\Actions\Payments;

use App\Actions\Payments\UpdatePaymentStatusManually;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePaymentStatusManuallyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_the_payment_status(): void
    {
        $currentStatus = PaymentStatus::factory()->create(['name' => 'Pendente', 'slug' => 'pendente']);
        $newStatus = PaymentStatus::factory()->create(['name' => 'Autorizado', 'slug' => 'autorizado']);
        $payment = Payment::factory()->create(['status_id' => $currentStatus->id]);
        $user = User::factory()->create();

        $updated = (new UpdatePaymentStatusManually)->execute($payment, $newStatus, $user);

        $this->assertSame($newStatus->id, $updated->status_id);
        $this->assertSame($newStatus->id, $payment->fresh()->status_id);
    }

    public function test_it_creates_exactly_one_audit_log_row_with_data_before_and_data_after_and_the_author(): void
    {
        $currentStatus = PaymentStatus::factory()->create(['name' => 'Pendente', 'slug' => 'pendente']);
        $newStatus = PaymentStatus::factory()->create(['name' => 'Autorizado', 'slug' => 'autorizado']);
        $payment = Payment::factory()->create(['status_id' => $currentStatus->id]);
        $user = User::factory()->create();

        (new UpdatePaymentStatusManually)->execute($payment, $newStatus, $user);

        $this->assertSame(1, AuditLog::query()->count());

        $auditLog = AuditLog::query()->first();
        $this->assertSame('payment', $auditLog->entity);
        $this->assertSame($payment->id, $auditLog->entity_id);
        $this->assertSame('manual_status_change', $auditLog->action);
        $this->assertSame($user->id, $auditLog->user_id);
        $this->assertSame(['status_id' => $currentStatus->id], $auditLog->data_before);
        $this->assertSame(['status_id' => $newStatus->id], $auditLog->data_after);
    }

    public function test_it_creates_a_new_audit_log_row_for_every_call_instead_of_reusing_one(): void
    {
        $statusA = PaymentStatus::factory()->create(['name' => 'Pendente', 'slug' => 'pendente']);
        $statusB = PaymentStatus::factory()->create(['name' => 'Autorizado', 'slug' => 'autorizado']);
        $statusC = PaymentStatus::factory()->create(['name' => 'Expirado', 'slug' => 'expirado']);
        $payment = Payment::factory()->create(['status_id' => $statusA->id]);
        $user = User::factory()->create();

        (new UpdatePaymentStatusManually)->execute($payment, $statusB, $user);
        (new UpdatePaymentStatusManually)->execute($payment->fresh(), $statusC, $user);

        $this->assertSame(2, AuditLog::query()->count());
    }
}
