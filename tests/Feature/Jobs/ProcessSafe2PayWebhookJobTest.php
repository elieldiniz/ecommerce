<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessSafe2PayWebhookJob;
use App\Models\IntegrationQueueJob;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentStatus;
use App\Models\Refund;
use Database\Seeders\OrderStatusSeeder;
use Database\Seeders\PaymentStatusSeeder;
use Database\Seeders\QueueJobStatusSeeder;
use Database\Seeders\RefundReasonSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
