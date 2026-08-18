<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessGfsisWebhookJob;
use App\Models\GfsisEvent;
use App\Models\GfsisStatus;
use App\Models\OrderItemGfsis;
use Database\Seeders\GfsisStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessGfsisWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GfsisStatusSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $dataAtualizacao, string $status = 'APROVADO'): array
    {
        return [
            'dataCriacao' => $dataAtualizacao,
            'dataAtualizacao' => $dataAtualizacao,
            'status' => $status,
            'dataValidade' => '24/08/2027 09:03',
        ];
    }

    public function test_a_valid_data_atualizacao_applies_the_transition_and_records_processed_at(): void
    {
        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => GfsisStatus::query()->where('slug', 'enviado_gfsis')->value('id'),
            'status_synced_at' => '2026-08-01 00:00:00',
        ]);

        $gfsisEvent = GfsisEvent::factory()->create([
            'gfsis_order_id' => $orderItemGfsis->gfsis_order_id,
            'payload' => $this->payload('24/08/2026'),
            'processed_at' => null,
        ]);

        (new ProcessGfsisWebhookJob($gfsisEvent))->handle();

        $orderItemGfsis->refresh();
        $gfsisEvent->refresh();

        $this->assertSame(
            GfsisStatus::query()->where('slug', 'aprovado')->value('id'),
            $orderItemGfsis->status_id
        );
        $this->assertNotNull($gfsisEvent->processed_at);
        $this->assertNull($gfsisEvent->error);
    }

    public function test_processed_at_is_recorded_even_when_the_transition_is_blocked_by_non_regression(): void
    {
        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => GfsisStatus::query()->where('slug', 'aprovado')->value('id'),
            'status_synced_at' => '2026-08-24 00:00:00',
        ]);

        $gfsisEvent = GfsisEvent::factory()->create([
            'gfsis_order_id' => $orderItemGfsis->gfsis_order_id,
            'payload' => $this->payload('24/08/2026', 'CANCELADO'),
            'processed_at' => null,
        ]);

        (new ProcessGfsisWebhookJob($gfsisEvent))->handle();

        $orderItemGfsis->refresh();
        $gfsisEvent->refresh();

        $this->assertSame(
            GfsisStatus::query()->where('slug', 'aprovado')->value('id'),
            $orderItemGfsis->status_id
        );
        $this->assertNotNull($gfsisEvent->processed_at);
    }

    public function test_an_orphan_event_with_an_unknown_gfsis_order_id_records_an_error_without_throwing(): void
    {
        $gfsisEvent = GfsisEvent::factory()->create([
            'gfsis_order_id' => 999999999,
            'payload' => $this->payload('24/08/2026'),
            'processed_at' => null,
        ]);

        (new ProcessGfsisWebhookJob($gfsisEvent))->handle();

        $gfsisEvent->refresh();
        $this->assertNotNull($gfsisEvent->error);
        $this->assertNotNull($gfsisEvent->processed_at);
    }

    public function test_an_event_with_a_null_gfsis_order_id_records_an_error_without_throwing(): void
    {
        $gfsisEvent = GfsisEvent::factory()->create([
            'gfsis_order_id' => null,
            'payload' => $this->payload('24/08/2026'),
            'processed_at' => null,
        ]);

        (new ProcessGfsisWebhookJob($gfsisEvent))->handle();

        $gfsisEvent->refresh();
        $this->assertNotNull($gfsisEvent->error);
        $this->assertNotNull($gfsisEvent->processed_at);
    }

    public function test_a_malformed_data_atualizacao_that_fails_even_the_fallback_records_an_error_without_throwing(): void
    {
        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => null,
            'status_synced_at' => null,
        ]);

        $gfsisEvent = GfsisEvent::factory()->create([
            'gfsis_order_id' => $orderItemGfsis->gfsis_order_id,
            'payload' => $this->payload('not-a-date-at-all'),
            'processed_at' => null,
        ]);

        (new ProcessGfsisWebhookJob($gfsisEvent))->handle();

        $gfsisEvent->refresh();
        $this->assertNotNull($gfsisEvent->error);
    }
}
