<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessSafe2PayWebhookJob;
use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Models\IntegrationQueueJob;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentStatus;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PaymentRefundedNotification;
use Database\Seeders\OrderStatusSeeder;
use Database\Seeders\PaymentStatusSeeder;
use Database\Seeders\QueueJobStatusSeeder;
use Database\Seeders\RefundReasonSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcessSafe2PayWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaymentStatusSeeder::class);
        $this->seed(OrderStatusSeeder::class);
        $this->seed(QueueJobStatusSeeder::class);
        $this->seed(RefundReasonSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $statusCode): array
    {
        return [
            'IdTransaction' => '999999999',
            'TransactionStatus' => ['Id' => $statusCode, 'Code' => (string) $statusCode],
            'PaymentMethod' => ['Id' => 1, 'Code' => '6', 'Name' => 'Pix'],
            'Reference' => 'PED-0001042',
        ];
    }

    public function test_an_authorized_payment_receiving_a_lower_weight_event_stays_authorized(): void
    {
        $authorized = PaymentStatus::query()->where('slug', 'authorized')->firstOrFail();

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '999999999',
            'status_id' => $authorized->id,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(1),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $this->assertSame($authorized->id, $payment->fresh()->status_id);
    }

    public function test_processed_at_is_filled_even_when_the_transition_is_blocked_by_weight(): void
    {
        $authorized = PaymentStatus::query()->where('slug', 'authorized')->firstOrFail();

        Payment::factory()->create([
            'gateway_transaction_id' => '999999999',
            'status_id' => $authorized->id,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(1),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $this->assertNotNull($event->fresh()->processed_at);
    }

    public function test_a_higher_weight_event_applies_the_status_transition(): void
    {
        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $authorized = PaymentStatus::query()->where('slug', 'authorized')->firstOrFail();

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '999999999',
            'status_id' => $pending->id,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $this->assertSame($authorized->id, $payment->fresh()->status_id);
        $this->assertNotNull($event->fresh()->processed_at);
    }

    public function test_an_orphan_event_without_a_matching_payment_records_an_error_without_throwing(): void
    {
        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => 'unknown-transaction-id',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $event->refresh();
        $this->assertNotNull($event->error);
        $this->assertNotNull($event->processed_at);
        $this->assertNull($event->payment_id);
    }

    public function test_the_first_authorization_of_an_order_pays_the_payment_and_the_order_and_enqueues_integration(): void
    {
        $pendingPayment = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $awaitingPayment = OrderStatus::query()->where('slug', 'awaiting_payment')->firstOrFail();
        $paid = OrderStatus::query()->where('slug', 'paid')->firstOrFail();

        $order = Order::factory()->create([
            'status_id' => $awaitingPayment->id,
            'paid_at' => null,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => '999999999',
            'status_id' => $pendingPayment->id,
            'paid_at' => null,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $payment->refresh();
        $order->refresh();

        $this->assertNotNull($payment->paid_at);
        $this->assertSame($paid->id, $order->status_id);
        $this->assertNotNull($order->paid_at);

        $this->assertSame(1, IntegrationQueueJob::query()
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->count());
    }

    public function test_the_first_authorization_of_an_order_generates_issuance_data_for_its_order_item(): void
    {
        $pendingPayment = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $awaitingPayment = OrderStatus::query()->where('slug', 'awaiting_payment')->firstOrFail();

        $order = Order::factory()->create([
            'status_id' => $awaitingPayment->id,
            'paid_at' => null,
        ]);

        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => '999999999',
            'status_id' => $pendingPayment->id,
            'paid_at' => null,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $issuanceData = IssuanceData::query()->where('order_item_id', $orderItem->id)->first();

        $this->assertNotNull($issuanceData);
        $this->assertSame(40, strlen($issuanceData->access_token));
        $this->assertTrue($issuanceData->access_token_expires_at->isFuture());
        $this->assertNull($issuanceData->filled_at);
    }

    public function test_the_first_authorization_of_an_order_dispatches_the_gfsis_registration_job(): void
    {
        Bus::fake();

        $pendingPayment = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $awaitingPayment = OrderStatus::query()->where('slug', 'awaiting_payment')->firstOrFail();

        $order = Order::factory()->create([
            'status_id' => $awaitingPayment->id,
            'paid_at' => null,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => '999999999',
            'status_id' => $pendingPayment->id,
            'paid_at' => null,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        Bus::assertDispatched(
            RegisterOrderItemWithGfsisJob::class,
            fn (RegisterOrderItemWithGfsisJob $job): bool => $job->order->is($order)
        );
    }

    public function test_the_dispatched_gfsis_registration_job_makes_no_http_call_before_issuance_data_is_complete(): void
    {
        Http::fake();

        $pendingPayment = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $awaitingPayment = OrderStatus::query()->where('slug', 'awaiting_payment')->firstOrFail();

        $order = Order::factory()->create([
            'status_id' => $awaitingPayment->id,
            'paid_at' => null,
        ]);

        OrderItem::factory()->create(['order_id' => $order->id]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => '999999999',
            'status_id' => $pendingPayment->id,
            'paid_at' => null,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        Http::assertNothingSent();
    }

    public function test_a_second_authorization_on_an_already_paid_order_does_not_move_the_order_again_and_opens_a_duplicate_refund(): void
    {
        $pendingPayment = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $paid = OrderStatus::query()->where('slug', 'paid')->firstOrFail();

        $orderPaidAt = now()->subHour();

        $order = Order::factory()->create([
            'status_id' => $paid->id,
            'paid_at' => $orderPaidAt,
        ]);

        $secondPayment = Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => 'second-attempt-txn',
            'status_id' => $pendingPayment->id,
            'paid_at' => null,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => 'second-attempt-txn',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $secondPayment->refresh();
        $order->refresh();

        $this->assertNotNull($secondPayment->paid_at);
        $this->assertSame($paid->id, $order->status_id);
        $this->assertSame($orderPaidAt->format('Y-m-d H:i:s'), $order->paid_at->format('Y-m-d H:i:s'));

        $refunds = Refund::query()->where('payment_id', $secondPayment->id)->get();
        $this->assertCount(1, $refunds);
        $this->assertSame('duplicate', $refunds->first()->reason->slug);
        $this->assertFalse($refunds->first()->requires_revocation);

        $this->assertSame(0, IntegrationQueueJob::query()->count());
    }

    public function test_a_second_authorization_on_an_already_paid_order_does_not_generate_a_second_issuance_data_row(): void
    {
        $pendingPayment = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $paid = OrderStatus::query()->where('slug', 'paid')->firstOrFail();

        $order = Order::factory()->create([
            'status_id' => $paid->id,
            'paid_at' => now()->subHour(),
        ]);

        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $existingIssuanceData = IssuanceData::factory()->create(['order_item_id' => $orderItem->id]);

        $secondPayment = Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => 'second-attempt-txn',
            'status_id' => $pendingPayment->id,
            'paid_at' => null,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => 'second-attempt-txn',
            'payload' => $this->payload(3),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $this->assertSame(1, IssuanceData::query()->where('order_item_id', $orderItem->id)->count());
        $this->assertSame($existingIssuanceData->access_token, $existingIssuanceData->fresh()->access_token);
    }

    private function financeUser(): User
    {
        $financeRole = Role::query()->where('slug', 'finance')->firstOrFail();

        return User::factory()->create(['role_id' => $financeRole->id]);
    }

    public function test_transition_to_reversed_without_a_prior_refund_creates_one_and_notifies_finance(): void
    {
        Notification::fake();

        $financeUser = $this->financeUser();

        $authorized = PaymentStatus::query()->where('slug', 'authorized')->firstOrFail();

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '999999999',
            'status_id' => $authorized->id,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(6),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $reversed = PaymentStatus::query()->where('slug', 'reversed')->firstOrFail();
        $this->assertSame($reversed->id, $payment->fresh()->status_id);

        $refunds = Refund::query()->where('payment_id', $payment->id)->get();
        $this->assertCount(1, $refunds);
        $this->assertSame('other', $refunds->first()->reason->slug);

        Notification::assertSentTo($financeUser, PaymentRefundedNotification::class);
    }

    public function test_transition_to_reversed_with_an_existing_refund_does_not_create_a_second_one(): void
    {
        Notification::fake();

        $this->financeUser();

        $authorized = PaymentStatus::query()->where('slug', 'authorized')->firstOrFail();

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '999999999',
            'status_id' => $authorized->id,
        ]);

        Refund::factory()->create(['payment_id' => $payment->id]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(6),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $this->assertSame(1, Refund::query()->where('payment_id', $payment->id)->count());
    }

    public function test_boleto_code_7_marks_the_payment_expired_and_preserves_the_raw_gateway_status_code(): void
    {
        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $expired = PaymentStatus::query()->where('slug', 'expired')->firstOrFail();

        $payment = Payment::factory()->create([
            'gateway_transaction_id' => '999999999',
            'status_id' => $pending->id,
        ]);

        $event = PaymentEvent::factory()->create([
            'payment_id' => null,
            'gateway_transaction_id' => '999999999',
            'payload' => $this->payload(7),
            'processed_at' => null,
        ]);

        (new ProcessSafe2PayWebhookJob($event))->handle();

        $payment->refresh();

        $this->assertSame($expired->id, $payment->status_id);
        $this->assertSame('7', $payment->gateway_status_code);
    }

    public function test_the_full_card_status_cycle_produces_the_expected_final_status_and_dispute_alert(): void
    {
        Notification::fake();

        $financeUser = $this->financeUser();

        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();
        $underReview = PaymentStatus::query()->where('slug', 'under_review')->firstOrFail();
        $authorized = PaymentStatus::query()->where('slug', 'authorized')->firstOrFail();
        $reversed = PaymentStatus::query()->where('slug', 'reversed')->firstOrFail();
        $awaitingPayment = OrderStatus::query()->where('slug', 'awaiting_payment')->firstOrFail();

        $order = Order::factory()->create([
            'status_id' => $awaitingPayment->id,
            'paid_at' => null,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => '999999999',
            'status_id' => $pending->id,
        ]);

        $processCode = function (int $code): void {
            $event = PaymentEvent::factory()->create([
                'payment_id' => null,
                'gateway_transaction_id' => '999999999',
                'payload' => $this->payload($code),
                'processed_at' => null,
            ]);

            (new ProcessSafe2PayWebhookJob($event))->handle();
        };

        // 14 (Pré-Autorizado) -> under_review
        $processCode(14);
        $this->assertSame($underReview->id, $payment->fresh()->status_id);

        // 3 (Autorizado) -> authorized
        $processCode(3);
        $this->assertSame($authorized->id, $payment->fresh()->status_id);

        // 8 (Recusado) -> denied, peso menor que authorized, bloqueado
        $processCode(8);
        $this->assertSame($authorized->id, $payment->fresh()->status_id);

        // 5 (Em disputa) -> under_review, bloqueado pelo peso, mas dispara alerta mesmo assim
        $processCode(5);
        $this->assertSame($authorized->id, $payment->fresh()->status_id);

        // 13 (Chargeback) -> reversed, cria refund com motivo chargeback
        $processCode(13);
        $this->assertSame($reversed->id, $payment->fresh()->status_id);

        // 15 (Devolução de Contestação) -> authorized, peso menor que reversed, bloqueado
        $processCode(15);
        $this->assertSame($reversed->id, $payment->fresh()->status_id);

        // 6 (Estornado) -> reversed, mesmo peso, bloqueado (não regride nem reaplica)
        $processCode(6);
        $this->assertSame($reversed->id, $payment->fresh()->status_id);

        $refunds = Refund::query()->where('payment_id', $payment->id)->get();
        $this->assertCount(1, $refunds);
        $this->assertSame('chargeback', $refunds->first()->reason->slug);

        Notification::assertSentToTimes($financeUser, PaymentRefundedNotification::class, 2);
    }
}
