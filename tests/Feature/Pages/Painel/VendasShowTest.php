<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Customer;
use App\Models\GfsisEvent;
use App\Models\GfsisStatus;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
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
        $response->assertSeeInOrder(['ID no gateway', 'SF2P-TESTE', 'End-to-end', 'E-TESTE-001']);
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

    public function test_conteudo_estatico_de_origem_da_venda_e_integracao_permanece_identico(): void
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

        $response->assertSee('Integração');
        $response->assertSee('gfsis_order_id');
        $response->assertSee('Código GFSIS');
        $response->assertSee('Status GFSIS');
        $response->assertSee('Reenviar ao GFSIS');

        $content = $response->getContent();
        $mainContent = substr($content, strpos($content, 'min-w-0 flex-1'));
        $this->assertSame(0, substr_count($mainContent, 'method="POST"'), 'A área de conteúdo não deve ter formulário embutido (o form de logout da sidebar é esperado).');
        $this->assertStringNotContainsString('wire:click', $mainContent);
    }

    public function test_linha_do_tempo_reflete_eventos_reais_do_pedido_em_ordem_cronologica(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create([
            'created_at' => '2026-08-10 14:22:00',
            'paid_at' => '2026-08-10 14:23:00',
        ]);
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        IssuanceData::factory()->create(['order_item_id' => $item->id, 'filled_at' => '2026-08-10 14:30:00']);
        $gfsis = OrderItemGfsis::factory()->create(['order_item_id' => $item->id, 'sent_at' => '2026-08-10 14:35:00']);
        GfsisEvent::factory()->create(['gfsis_order_id' => $gfsis->gfsis_order_id, 'payload' => ['status' => 'APROVADO'], 'received_at' => '2026-08-10 16:00:00']);
        GfsisEvent::factory()->create(['gfsis_order_id' => $gfsis->gfsis_order_id, 'payload' => ['status' => 'EMITIDO'], 'received_at' => '2026-08-10 17:05:00']);
        // ENVIADO já é representado por "Enviado ao GFSIS" (sent_at) e não deve duplicar a linha do tempo.
        GfsisEvent::factory()->create(['gfsis_order_id' => $gfsis->gfsis_order_id, 'payload' => ['status' => 'ENVIADO'], 'received_at' => '2026-08-10 14:36:00']);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();
        $response->assertSee('Linha do tempo');
        $response->assertSeeInOrder([
            'Pedido criado',
            'Pagamento autorizado',
            'Dados de emissão preenchidos',
            'Enviado ao GFSIS',
            'Aprovado pelo GFSIS (videoconferência validada)',
            'Certificado emitido',
        ]);
        $response->assertSee('webhook');
        $response->assertSee('sistema');
        $response->assertSee('cliente');
        $response->assertSee('fila');
        $response->assertSee('10/08/2026 14:22');
        $response->assertSee('10/08/2026 17:05');

        // Um segundo pedido não pode "vazar" eventos do primeiro na linha do tempo.
        $otherOrder = Order::factory()->create(['created_at' => '2026-08-11 09:00:00']);
        OrderItem::factory()->create(['order_id' => $otherOrder->id]);

        $otherResponse = $this->get("/painel/vendas/{$otherOrder->id}/");
        $otherResponse->assertDontSee('Certificado emitido');
    }

    public function test_bloco_integracao_exibe_dados_reais_do_order_item_gfsis(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        $status = GfsisStatus::factory()->create(['slug' => 'aprovado', 'name' => 'Aprovado']);
        OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'gfsis_order_id' => 4794531,
            'gfsis_code' => '102930',
            'status_id' => $status->id,
            'status_synced_at' => '2026-08-24 09:03:00',
            'certificate_expires_at' => '2027-08-24',
            'attempts' => 2,
        ]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();
        $response->assertSee('4794531');
        $response->assertSee('102930');
        $response->assertSee('Aprovado');
        $response->assertSee('24/08/2027');
    }

    public function test_bloco_integracao_sem_order_item_gfsis_exibe_placeholder_sem_erro(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get("/painel/vendas/{$order->id}/");

        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'Integração</h3>');
        $end = strpos($html, '</dl>', $start);
        $integracaoBlock = substr($html, $start, $end - $start);
        $this->assertSame(7, substr_count($integracaoBlock, '—'));
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
