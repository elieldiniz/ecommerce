<?php

namespace Tests\Unit\Actions\Gfsis;

use App\Actions\Gfsis\ApplyGfsisStatusTransition;
use App\Models\GfsisStatus;
use App\Models\OrderItemGfsis;
use Database\Seeders\GfsisStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplyGfsisStatusTransitionTest extends TestCase
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
    private function payload(string $status, string $dataAtualizacao, ?string $dataValidade = null): array
    {
        $payload = [
            'identificador' => 4794531,
            'dataCriacao' => $dataAtualizacao,
            'dataAtualizacao' => $dataAtualizacao,
            'status' => $status,
        ];

        if ($dataValidade !== null) {
            $payload['dataValidade'] = $dataValidade;
        }

        return $payload;
    }

    public function test_a_payload_with_an_earlier_or_equal_data_atualizacao_does_not_change_anything(): void
    {
        $statusId = GfsisStatus::query()->where('slug', 'aprovado')->value('id');

        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => $statusId,
            'status_synced_at' => '2026-08-24 00:00:00',
            'certificate_expires_at' => null,
        ]);

        (new ApplyGfsisStatusTransition)->execute(
            $orderItemGfsis,
            $this->payload('CANCELADO', '24/08/2026', '24/08/2027 09:03')
        );

        $orderItemGfsis->refresh();
        $this->assertSame($statusId, $orderItemGfsis->status_id);
        $this->assertSame('2026-08-24 00:00:00', $orderItemGfsis->status_synced_at->toDateTimeString());
        $this->assertNull($orderItemGfsis->certificate_expires_at);
    }

    public function test_a_payload_with_a_later_data_atualizacao_applies_the_update_including_certificate_expiration(): void
    {
        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => GfsisStatus::query()->where('slug', 'enviado_gfsis')->value('id'),
            'status_synced_at' => '2026-08-20 00:00:00',
            'certificate_expires_at' => null,
        ]);

        (new ApplyGfsisStatusTransition)->execute(
            $orderItemGfsis,
            $this->payload('APROVADO', '24/08/2026', '24/08/2027 09:03')
        );

        $orderItemGfsis->refresh();
        $this->assertSame(
            GfsisStatus::query()->where('slug', 'aprovado')->value('id'),
            $orderItemGfsis->status_id
        );
        $this->assertSame('2026-08-24 00:00:00', $orderItemGfsis->status_synced_at->toDateTimeString());
        $this->assertSame('2027-08-24', $orderItemGfsis->certificate_expires_at->toDateString());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function statusMappingProvider(): array
    {
        return [
            'CRIADO maps to enviado_gfsis' => ['CRIADO', 'enviado_gfsis'],
            'ENVIADO maps to enviado_gfsis' => ['ENVIADO', 'enviado_gfsis'],
            'APROVADO maps to aprovado' => ['APROVADO', 'aprovado'],
            'EMITIDO maps to emitido' => ['EMITIDO', 'emitido'],
            'RECUSADO maps to falha_envio' => ['RECUSADO', 'falha_envio'],
            'CANCELADO maps to cancelado' => ['CANCELADO', 'cancelado'],
        ];
    }

    #[DataProvider('statusMappingProvider')]
    public function test_each_status_value_maps_to_the_expected_slug(string $status, string $expectedSlug): void
    {
        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => null,
            'status_synced_at' => null,
        ]);

        (new ApplyGfsisStatusTransition)->execute($orderItemGfsis, $this->payload($status, '24/08/2026'));

        $orderItemGfsis->refresh();
        $this->assertSame(
            GfsisStatus::query()->where('slug', $expectedSlug)->value('id'),
            $orderItemGfsis->status_id
        );
    }

    public function test_data_atualizacao_in_the_primary_format_is_parsed_without_falling_back(): void
    {
        Log::shouldReceive('warning')->never();

        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => null,
            'status_synced_at' => null,
        ]);

        (new ApplyGfsisStatusTransition)->execute($orderItemGfsis, $this->payload('APROVADO', '24/08/2026'));

        $orderItemGfsis->refresh();
        $this->assertSame('2026-08-24 00:00:00', $orderItemGfsis->status_synced_at->toDateTimeString());
    }

    public function test_data_validade_in_the_primary_format_is_parsed_and_stored_in_certificate_expires_at(): void
    {
        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => null,
            'status_synced_at' => null,
            'certificate_expires_at' => null,
        ]);

        (new ApplyGfsisStatusTransition)->execute(
            $orderItemGfsis,
            $this->payload('APROVADO', '24/08/2026', '24/08/2027 09:03')
        );

        $orderItemGfsis->refresh();
        $this->assertSame('2027-08-24', $orderItemGfsis->certificate_expires_at->toDateString());
    }

    public function test_an_unexpected_date_format_falls_back_to_carbon_parse_and_logs_a_warning(): void
    {
        Log::shouldReceive('warning')->once();

        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'status_id' => null,
            'status_synced_at' => null,
        ]);

        $payload = $this->payload('APROVADO', '2026-08-24T00:00:00Z');

        (new ApplyGfsisStatusTransition)->execute($orderItemGfsis, $payload);

        $orderItemGfsis->refresh();
        $this->assertSame(
            GfsisStatus::query()->where('slug', 'aprovado')->value('id'),
            $orderItemGfsis->status_id
        );
        $this->assertSame('2026-08-24', $orderItemGfsis->status_synced_at->toDateString());
    }
}
