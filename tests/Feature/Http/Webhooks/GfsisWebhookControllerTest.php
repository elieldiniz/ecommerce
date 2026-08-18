<?php

namespace Tests\Feature\Http\Webhooks;

use App\Jobs\ProcessGfsisWebhookJob;
use App\Models\GfsisEvent;
use App\Models\OrderItemGfsis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GfsisWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(?int $identificador, string $status = 'APROVADO'): array
    {
        $payload = [
            'dataCriacao' => '24/08/2026',
            'dataAtualizacao' => '24/08/2026',
            'status' => $status,
            'dataValidade' => '24/08/2027 09:03',
        ];

        if ($identificador !== null) {
            $payload = ['identificador' => $identificador] + $payload;
        }

        return $payload;
    }

    public function test_a_webhook_with_a_known_identificador_records_one_event_and_responds_200(): void
    {
        Queue::fake();

        $orderItemGfsis = OrderItemGfsis::factory()->create();

        $response = $this->postJson('/webhooks/gfsis', $this->payload($orderItemGfsis->gfsis_order_id));

        $response->assertOk();
        $response->assertJson(['received' => true]);
        $this->assertSame(1, GfsisEvent::query()->count());
        $this->assertSame($orderItemGfsis->gfsis_order_id, GfsisEvent::query()->first()->gfsis_order_id);
    }

    public function test_a_webhook_with_an_unknown_identificador_still_records_one_event_without_exception(): void
    {
        Queue::fake();

        $response = $this->postJson('/webhooks/gfsis', $this->payload(999999999));

        $response->assertOk();
        $response->assertJson(['received' => true]);
        $this->assertSame(1, GfsisEvent::query()->count());
        $this->assertSame(999999999, GfsisEvent::query()->first()->gfsis_order_id);
    }

    public function test_a_webhook_without_identificador_records_an_event_with_null_gfsis_order_id(): void
    {
        Queue::fake();

        $response = $this->postJson('/webhooks/gfsis', $this->payload(null));

        $response->assertOk();
        $this->assertSame(1, GfsisEvent::query()->count());
        $this->assertNull(GfsisEvent::query()->first()->gfsis_order_id);
    }

    public function test_sending_the_same_body_three_times_creates_only_one_event_and_dispatches_at_most_one_job(): void
    {
        Queue::fake();

        $body = $this->payload(102930);

        $this->postJson('/webhooks/gfsis', $body)->assertOk();
        $this->postJson('/webhooks/gfsis', $body)->assertOk();
        $this->postJson('/webhooks/gfsis', $body)->assertOk();

        $this->assertSame(1, GfsisEvent::query()->count());
        Queue::assertPushed(ProcessGfsisWebhookJob::class, 1);
    }

    public function test_the_endpoint_responds_200_without_waiting_for_the_job_to_process(): void
    {
        Queue::fake();

        $response = $this->postJson('/webhooks/gfsis', $this->payload(102930));

        $response->assertOk();
        $response->assertJson(['received' => true]);
        Queue::assertPushed(ProcessGfsisWebhookJob::class);
    }

    public function test_an_invalid_json_body_responds_422_and_does_not_record_an_event(): void
    {
        Queue::fake();

        $response = $this->call('POST', '/webhooks/gfsis', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{invalid-json');

        $response->assertStatus(422);
        $this->assertSame(0, GfsisEvent::query()->count());
        Queue::assertNotPushed(ProcessGfsisWebhookJob::class);
    }
}
