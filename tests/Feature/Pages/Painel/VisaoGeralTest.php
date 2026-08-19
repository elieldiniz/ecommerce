<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\AdsConversion;
use App\Models\AdsConversionStatus;
use App\Models\GfsisStatus;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\OrderStatus;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

class VisaoGeralTest extends TestCase
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

    private function adsConversionStatus(string $slug): AdsConversionStatus
    {
        return AdsConversionStatus::where('slug', $slug)->first()
            ?? AdsConversionStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_view(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.visao-geral')->assertOk();
    }

    public function test_pagina_nao_quebra_sem_nenhum_pedido(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertOk();
        $response->assertSee(Number::currency(0, in: 'BRL', locale: 'pt_BR'), false);
        $response->assertSee('0%');
    }

    public function test_block_indicadores_reflete_pedidos_reais(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->create(['status_id' => $this->orderStatus('cart')->id, 'paid_at' => null, 'total' => 100]);
        Order::factory()->create(['status_id' => $this->orderStatus('paid')->id, 'paid_at' => now(), 'total' => 200]);
        Order::factory()->create(['status_id' => $this->orderStatus('paid')->id, 'paid_at' => now(), 'total' => 300]);

        $response = $this->get('/painel/');

        $response->assertSee('Faturamento');
        $response->assertSee(Number::currency(500, in: 'BRL', locale: 'pt_BR'), false);
        $response->assertSee('Ticket médio');
        $response->assertSee(Number::currency(250, in: 'BRL', locale: 'pt_BR'), false);
        $response->assertSee('Taxa de conversão');
        $response->assertSee('67%');
        $response->assertSee('Aguardando dados');
        $response->assertSee('Falha de integração');
    }

    public function test_block_funil_reflete_pedidos_reais(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $cart = $this->orderStatus('cart');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');
        $dataComplete = $this->fulfillmentStatus('data_complete');
        $sentToGfsis = $this->fulfillmentStatus('sent_to_gfsis');

        Order::factory()->create(['status_id' => $cart->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => null]);
        Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()]);
        Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $dataComplete->id, 'paid_at' => now()]);

        $enviado = Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $sentToGfsis->id, 'paid_at' => now()]);
        $itemEnviado = OrderItem::factory()->create(['order_id' => $enviado->id]);
        OrderItemGfsis::factory()->create(['order_item_id' => $itemEnviado->id, 'status_id' => $this->gfsisStatus('enviado_gfsis')->id]);

        $emitido = Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $sentToGfsis->id, 'paid_at' => now()]);
        $itemEmitido = OrderItem::factory()->create(['order_id' => $emitido->id]);
        OrderItemGfsis::factory()->create(['order_item_id' => $itemEmitido->id, 'status_id' => $this->gfsisStatus('emitido')->id]);

        $response = $this->get('/painel/');

        $response->assertSeeInOrder(['Pedidos criados', 'Pagos', 'Dados completos', 'Enviados ao GFSIS', 'Emitidos']);
        $response->assertSee('80%');
        $response->assertSee('75%');
        $response->assertSee('67%');
        $response->assertSee('50%');
    }

    public function test_block_exige_acao_reflete_filas_reais(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(3)]);

        $orderFalha = Order::factory()->create();
        $itemFalha = OrderItem::factory()->create(['order_id' => $orderFalha->id]);
        $gfsisFalha = OrderItemGfsis::factory()->create(['order_item_id' => $itemFalha->id, 'status_id' => $this->gfsisStatus('falha_envio')->id]);
        $gfsisFalha->forceFill(['created_at' => now()->subDay()])->save();

        $orderConversao = Order::factory()->create();
        AdsConversion::factory()->create(['order_id' => $orderConversao->id, 'status_id' => $this->adsConversionStatus('failed')->id]);

        Refund::factory()->create(['completed_at' => null, 'requested_at' => now()->subDays(5)]);

        $orderVencendo = Order::factory()->create();
        $itemVencendo = OrderItem::factory()->create(['order_id' => $orderVencendo->id]);
        OrderItemGfsis::factory()->vencendo()->create(['order_item_id' => $itemVencendo->id]);

        $response = $this->get('/painel/');

        $response->assertSee('Exige ação');
        $response->assertSeeInOrder(['Pagos sem dados de emissão', '1', '3 dias']);
        $response->assertSeeInOrder(['Falha de envio ao GFSIS', '1', '1 dia']);
        $response->assertSeeInOrder(['Conversões não enviadas', '1']);
        $response->assertSeeInOrder(['Reembolsos pendentes', '1', '5 dias']);
        $response->assertSeeInOrder(['Certificados vencendo em 30 dias', '1', '—']);
    }

    public function test_botao_abrir_linka_para_recuperacao_nas_duas_primeiras_filas(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertSee('href="'.route('painel.recuperacao').'"', false);
    }

    public function test_block_vendas_por_dia_renders_chart_placeholder(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertSee('Vendas por dia');
        $response->assertSee('Gráfico de barras · vendas por dia');
    }
}
