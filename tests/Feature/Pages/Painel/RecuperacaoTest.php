<?php

namespace Tests\Feature\Pages\Painel;

use App\Actions\Gfsis\GenerateIssuanceAccessToken;
use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Mail\IssuanceAccessLinkMail;
use App\Models\GfsisStatus;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class RecuperacaoTest extends TestCase
{
    use RefreshDatabase;

    private function orderStatus(string $slug): OrderStatus
    {
        return OrderStatus::where('slug', $slug)->first()
            ?? OrderStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    private function fulfillmentStatus(string $slug): OrderFulfillmentStatus
    {
        return OrderFulfillmentStatus::where('slug', $slug)->first()
            ?? OrderFulfillmentStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    private function gfsisStatus(string $slug): GfsisStatus
    {
        return GfsisStatus::where('slug', $slug)->first()
            ?? GfsisStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    private function makeQueuedOrder(): Order
    {
        $order = Order::factory()->create([
            'status_id' => $this->orderStatus('paid')->id,
            'fulfillment_status_id' => $this->fulfillmentStatus('awaiting_data')->id,
            'paid_at' => now()->subDays(2),
        ]);

        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order->fresh();
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/recuperacao/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_view(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.recuperacao')->assertOk();
    }

    public function test_block_indicadores_com_fila_vazia(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('Pagos sem dados');
        $response->assertSee('Recuperados em 7 dias');
        $response->assertSee('Mais antigo');
        $response->assertSee('Falha de envio');
        $response->assertSee('—');
    }

    public function test_kpi_pagos_sem_dados_reflete_contagem_real(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makeQueuedOrder();
        $this->makeQueuedOrder();

        Livewire::test('pages::painel.recuperacao')
            ->assertSee('2');
    }

    public function test_kpi_recuperados_7_dias_calcula_percentual(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $dataComplete = $this->fulfillmentStatus('data_complete');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $dataComplete->id, 'paid_at' => now()->subDays(1)]);
        Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(1)]);

        Livewire::test('pages::painel.recuperacao')
            ->assertSee('50%');
    }

    public function test_table_fila_ordenada_por_paid_at_sem_coluna_contatos(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        $antigo = Order::factory()->create(['number' => 'PED-ANTIGO0', 'status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(5)]);
        OrderItem::factory()->create(['order_id' => $antigo->id]);

        $recente = Order::factory()->create(['number' => 'PED-RECENTE', 'status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(1)]);
        OrderItem::factory()->create(['order_id' => $recente->id]);

        $response = $this->get('/painel/recuperacao/');

        $response->assertSeeInOrder(['PED-ANTIGO0', 'PED-RECENTE']);
        $response->assertDontSee('Contatos');
    }

    public function test_reenviar_link_regenera_token_e_envia_email(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());

        $order = $this->makeQueuedOrder();
        (new GenerateIssuanceAccessToken)->execute($order);
        $tokenAntigo = IssuanceData::query()->firstOrFail()->access_token;

        Livewire::test('pages::painel.recuperacao')
            ->call('resendLink', $order->id)
            ->assertOk();

        $this->assertNotSame($tokenAntigo, IssuanceData::query()->firstOrFail()->access_token);
        Mail::assertSent(IssuanceAccessLinkMail::class);
    }

    public function test_table_regua_automatica_permanece_estatica(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('Régua automática');
        $response->assertSeeInOrder(['Imediato', 'E-mail']);
        $response->assertSeeInOrder(['2 horas', 'WhatsApp']);
        $response->assertSeeInOrder(['24 horas', 'E-mail']);
        $response->assertSeeInOrder(['3 dias', 'WhatsApp']);
        $response->assertSeeInOrder(['5 dias', 'Painel']);
    }

    public function test_block_falhas_de_integracao_exibe_last_error_e_attempts(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create(['number' => 'PED-FALHOU1']);
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'status_id' => $this->gfsisStatus('falha_envio')->id,
            'last_error' => 'Timeout ao enviar dados ao GFSIS',
            'attempts' => 3,
        ]);

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('PED-FALHOU1');
        $response->assertSee('Timeout ao enviar dados ao GFSIS');
        $response->assertSee('Corrigir e reenviar');
    }

    public function test_corrigir_e_reenviar_despacha_job_do_gfsis(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'status_id' => $this->gfsisStatus('falha_envio')->id,
        ]);

        Livewire::test('pages::painel.recuperacao')
            ->call('fixAndResend', $orderItemGfsis->id)
            ->assertOk();

        Queue::assertPushed(RegisterOrderItemWithGfsisJob::class);
    }

    public function test_botoes_de_acao_ficam_desabilitados_durante_requisicao(): void
    {
        $this->actingAs(User::factory()->create());

        $order = $this->makeQueuedOrder();

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('wire:loading.attr="disabled"', false);
    }
}
