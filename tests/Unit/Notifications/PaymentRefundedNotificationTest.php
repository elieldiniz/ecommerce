<?php

namespace Tests\Unit\Notifications;

use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PaymentRefundedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentRefundedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_contains_the_order_number_and_the_payment_amount(): void
    {
        $payment = Payment::factory()->create(['gross_amount' => 199.90]);
        $payment->load('order');

        $mailMessage = (new PaymentRefundedNotification($payment))->toMail(new AnonymousNotifiable);

        $rendered = collect($mailMessage->introLines)->implode(' ');

        $this->assertStringContainsString($payment->order->number, $rendered);
        $this->assertStringContainsString('199,90', $rendered);
    }

    public function test_send_to_finance_team_notifies_only_users_with_the_finance_role(): void
    {
        Notification::fake();

        $financeRole = Role::factory()->create(['slug' => 'finance']);
        $otherRole = Role::factory()->create(['slug' => 'operations']);

        $financeUser = User::factory()->create(['role_id' => $financeRole->id]);
        $otherUser = User::factory()->create(['role_id' => $otherRole->id]);

        $payment = Payment::factory()->create();

        PaymentRefundedNotification::sendToFinanceTeam($payment);

        Notification::assertSentTo($financeUser, PaymentRefundedNotification::class);
        Notification::assertNotSentTo($otherUser, PaymentRefundedNotification::class);
    }
}
