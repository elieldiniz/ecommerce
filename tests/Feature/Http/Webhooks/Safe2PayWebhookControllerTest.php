<?php

namespace Tests\Feature\Http\Webhooks;

use App\Jobs\ProcessSafe2PayWebhookJob;
use App\Models\PaymentEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Safe2PayWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(string $idTransaction = '999999999'): array
    {
        return [
            'IdTransaction' => $idTransaction,
            'TransactionStatus' => ['Id' => 3, 'Code' => '3', 'Name' => 'Autorizado'],
            'PaymentMethod' => ['Id' => 1, 'Code' => '6', 'Name' => 'Pix'],
            'Reference' => 'PED-0001042',
        ];
    }

    public function test_a_webhook_with_an_unknown_gateway_transaction_id_still_records_one_payment_event(): void
    {
        Queue::fake();

        $response = $this->postJson('/webhooks/safe2pay', $this->payload('unknown-transaction-id'));

        $response->assertOk();
        $this->assertSame(1, PaymentEvent::query()->count());

        $event = PaymentEvent::query()->first();
        $this->assertSame('unknown-transaction-id', $event->gateway_transaction_id);
        $this->assertNull($event->payment_id);
    }

    public function test_sending_the_same_body_three_times_dispatches_at_most_one_job(): void
    {
        Queue::fake();

        $body = $this->payload();

        $this->postJson('/webhooks/safe2pay', $body)->assertOk();
        $this->postJson('/webhooks/safe2pay', $body)->assertOk();
        $this->postJson('/webhooks/safe2pay', $body)->assertOk();

        Queue::assertPushed(ProcessSafe2PayWebhookJob::class, 1);
    }

    public function test_the_endpoint_responds_200_without_waiting_for_the_job_to_process(): void
    {
        Queue::fake();

        $response = $this->postJson('/webhooks/safe2pay', $this->payload());

        $response->assertOk();
        $response->assertJson(['received' => true]);
        Queue::assertPushed(ProcessSafe2PayWebhookJob::class);
    }
}
