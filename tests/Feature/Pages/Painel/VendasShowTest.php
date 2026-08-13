<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendasShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/vendas/1042/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_vendas_show_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/1042/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.vendas.show');
    }

    public function test_block_cabecalho_renders_order_number_date_client_and_two_status_badges(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/1042/');

        $response->assertSee('Pedido #1042');
        $response->assertSee('Criado em 10/08/2026');
        $response->assertSee('Maria Aparecida Souza');
        $response->assertSee('Pago');
        $response->assertSee('Emitido');
    }

    public function test_table_itens_renders_five_columns(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/1042/');

        $response->assertSeeInOrder(['SKU', 'Produto', 'Titular', 'Preço tabela', 'Preço praticado']);
        $response->assertSee('ECPF-A1-12M');
    }

    public function test_block_financeiro_renders_valores_and_pagamento_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/1042/');

        $response->assertSee('Valores');
        $response->assertSee('Subtotal');
        $response->assertSee('Desconto cupom');
        $response->assertSee('Desconto Pix');
        $response->assertSee('Taxa do gateway');
        $response->assertSee('Líquido previsto');

        $response->assertSee('Pagamento');
        $response->assertSee('Método');
        $response->assertSee('ID no gateway');
        $response->assertSee('TXID');
        $response->assertSee('End-to-end');
        $response->assertSee('Previsão de repasse');
    }

    public function test_block_emissao_gfsis_renders_titular_and_integracao_cards_and_resend_button(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/1042/');

        $response->assertSee('Dados do titular');
        $response->assertSee('Integração');
        $response->assertSee('gfsis_order_id');
        $response->assertSee('Código GFSIS');
        $response->assertSee('Status GFSIS');
        $response->assertSee('Reenviar ao GFSIS');

        $this->assertSame(0, substr_count($response->getContent(), 'method="POST"'));
    }

    public function test_table_origem_da_venda_renders_seven_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/1042/');

        $response->assertSee('Origem da venda');
        $response->assertSee('Campanha');
        $response->assertSee('Origem e meio');
        $response->assertSee('gclid');
        $response->assertSee('Página de entrada');
        $response->assertSee('Dispositivo');
        $response->assertSee('Sessões até a compra');
        $response->assertSee('Status de conversão enviada');
    }

    public function test_block_linha_do_tempo_renders_six_events_in_chronological_order_with_origin(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/1042/');

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
    }
}
