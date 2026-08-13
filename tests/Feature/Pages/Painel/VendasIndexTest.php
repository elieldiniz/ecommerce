<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendasIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/vendas/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_vendas_index_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.vendas.index');
    }

    public function test_block_filtros_renders_seven_controls(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('Período');
        $response->assertSee('Status do pagamento');
        $response->assertSee('Status da emissão');
        $response->assertSee('Forma de pagamento');
        $response->assertSee('Produto');
        $response->assertSee('Origem');
        $response->assertSee('Buscar por nome, documento ou número do pedido');
    }

    public function test_table_renders_seven_columns_pagamento_and_emissao_separated_with_mock_pagination(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSeeInOrder(['Pedido', 'Cliente', 'Produto', 'Valor', 'Pagamento', 'Emissão', 'Data']);
        $response->assertSee('Mostrando 1 a 25 de 312');

        preg_match_all('/<tr>/', $response->getContent(), $rows);
        $this->assertGreaterThanOrEqual(6, count($rows[0]));
    }

    public function test_block_acoes_em_lote_renders_three_buttons_without_real_action(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('Exportar CSV');
        $response->assertSee('Reenviar ao GFSIS');
        $response->assertSee('Disparar recuperação');

        $this->assertSame(0, substr_count($response->getContent(), 'method="POST"'));
    }
}
