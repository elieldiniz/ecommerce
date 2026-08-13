<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisaoGeralTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_visao_geral_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.visao-geral');
    }

    public function test_block_indicadores_renders_five_kpi_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertSee('Faturamento');
        $response->assertSee('R$ 78.420,00');
        $response->assertSee('Ticket médio');
        $response->assertSee('R$ 251,35');
        $response->assertSee('Taxa de conversão');
        $response->assertSee('68%');
        $response->assertSee('Aguardando dados');
        $response->assertSee('Falha de integração');
    }

    public function test_block_funil_renders_five_stages_with_conversion(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertSeeInOrder([
            'Pedidos criados',
            'Pagos',
            'Dados completos',
            'Enviados ao GFSIS',
            'Emitidos',
        ]);
        $response->assertSee('83%');
        $response->assertSee('89%');
        $response->assertSee('97%');
        $response->assertSee('95%');
        $response->assertSee('A maior queda revela o gargalo.');
    }

    public function test_block_exige_acao_renders_five_queues(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertSee('Exige ação');
        $response->assertSee('Pagos sem dados de emissão');
        $response->assertSee('Falha de envio ao GFSIS');
        $response->assertSee('Conversões não enviadas');
        $response->assertSee('Reembolsos pendentes');
        $response->assertSee('Certificados vencendo em 30 dias');
        $response->assertSee('Abrir');
    }

    public function test_block_vendas_por_dia_renders_chart_placeholder(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/');

        $response->assertSee('Vendas por dia');
        $response->assertSee('Gráfico de barras · vendas por dia');
    }
}
