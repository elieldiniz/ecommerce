<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Customer;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendasShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $order = Order::factory()->create();

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_vendas_show_view(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();
    }

    public function test_detalhe_exibe_dados_reais_do_pedido_e_cliente_sem_texto_fixo_do_mockup(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create(['legal_name' => 'Maria Teste']);
        $order = Order::factory()->create(['number' => 'PED-000001', 'customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();
        $response->assertSee('PED-000001');
        $response->assertSee('Maria Teste');
        $response->assertDontSee('Maria Aparecida Souza');
        $response->assertDontSee('#1042');
        $response->assertDontSee('ECPF-A1-12M');
    }

    public function test_id_inexistente_retorna_404(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/999999/');

        $response->assertNotFound();
    }

    public function test_bloco_pagamento_exibe_id_gateway_e_end_to_end_do_payment_mais_recente(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'gateway_transaction_id' => 'SF2P-TESTE',
            'end_to_end_id' => 'E-TESTE-001',
        ]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();
        $response->assertSee('SF2P-TESTE');
        $response->assertSee('E-TESTE-001');
    }

    public function test_pedido_sem_payment_renderiza_bloco_pagamento_so_com_placeholder(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'Pagamento</h3>');
        $end = strpos($html, '</dl>', $start);
        $pagamentoBlock = substr($html, $start, $end - $start);
        $this->assertSame(7, substr_count($pagamentoBlock, '—'));
    }

    public function test_item_sem_issuance_data_renderiza_bloco_dados_do_titular_so_com_placeholder(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();
        $response->assertSee('Dados do titular');

        $html = $response->getContent();
        $start = strpos($html, 'Dados do titular</h3>');
        $end = strpos($html, '</dl>', $start);
        $titularBlock = substr($html, $start, $end - $start);
        $this->assertSame(6, substr_count($titularBlock, '—'));
    }

    public function test_item_com_issuance_data_nao_preenchida_filled_at_nulo_renderiza_placeholder(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        IssuanceData::factory()->create(['order_item_id' => $item->id, 'filled_at' => null]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'Dados do titular</h3>');
        $end = strpos($html, '</dl>', $start);
        $titularBlock = substr($html, $start, $end - $start);
        $this->assertSame(6, substr_count($titularBlock, '—'));
    }

    public function test_conteudo_estatico_de_origem_da_venda_linha_do_tempo_e_integracao_permanece_identico(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();

        $response->assertSee('Origem da venda');
        $response->assertSee('Campanha');
        $response->assertSee('Origem e meio');
        $response->assertSee('gclid');
        $response->assertSee('Página de entrada');
        $response->assertSee('Dispositivo');
        $response->assertSee('Sessões até a compra');
        $response->assertSee('Status de conversão enviada');

        $response->assertSee('Linha do tempo');
        $response->assertSeeInOrder([
            'Pedido criado',
            'Pagamento autorizado',
            'Dados de emissão preenchidos',
            'Enviado ao GFSIS',
            'Videoconferência realizada',
            'Certificado emitido',
        ]);
        $response->assertSee('webhook');
        $response->assertSee('sistema');
        $response->assertSee('cliente');
        $response->assertSee('fila');

        $response->assertSee('Integração');
        $response->assertSee('gfsis_order_id');
        $response->assertSee('Código GFSIS');
        $response->assertSee('Status GFSIS');
        $response->assertSee('Reenviar ao GFSIS');

        $this->assertSame(0, substr_count($response->getContent(), 'method="POST"'));
        $this->assertStringNotContainsString('wire:click', $response->getContent());
    }

    public function test_status_cancelled_renderiza_badge_financeiro_variant_erro(): void
    {
        $this->actingAs(User::factory()->create());

        $cancelled = OrderStatus::where('slug', 'cancelled')->first()
            ?? OrderStatus::factory()->create(['slug' => 'cancelled', 'name' => 'Cancelado']);

        $order = Order::factory()->create(['status_id' => $cancelled->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();
        $response->assertSee('bg-[#fbe9e9]', false);
    }
}
