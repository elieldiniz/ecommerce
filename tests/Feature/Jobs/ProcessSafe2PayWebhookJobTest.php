<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessSafe2PayWebhookJob;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PaymentStatus;
use Database\Seeders\PaymentStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessSafe2PayWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaymentStatusSeeder::class);
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
}
