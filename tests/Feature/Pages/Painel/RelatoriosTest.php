<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatoriosTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/relatorios/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_relatorios_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/relatorios/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.relatorios');
    }

    public function test_block_selecao_renders_nine_report_cards_with_vendas_por_periodo_selected_by_default(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Vendas por período');
        $response->assertSee('Vendas por produto');
        $response->assertSee('Funil operacional');
        $response->assertSee('Pagos sem dados');
        $response->assertSee('Base de renovação');
        $response->assertSee('Atribuição');
        $response->assertSee('Conciliação do gateway');
        $response->assertSee('Estornos');
        $response->assertSee('Cupons');

        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, 'border-2 border-brand bg-highlight'));

        preg_match('/<button[^>]*data-report="vendas-por-periodo"[^>]*>.*?<\/button>/s', $content, $matches);
        $this->assertStringContainsString('border-2 border-brand bg-highlight', $matches[0] ?? '');
    }

    public function test_example_vendas_por_periodo_renders_filters_four_kpis_chart_placeholder_daily_table_and_export_buttons(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Período');
        $response->assertSee('Produto');
        $response->assertSee('Forma de pagamento');
        $response->assertSee('Origem');
        $response->assertSee('Buscar');

        $response->assertSee('Faturamento');
        $response->assertSee('Pedidos');
        $response->assertSee('Ticket médio');
        $response->assertSee('Descontos');

        $response->assertSee('Gráfico de linha · faturamento diário');

        $response->assertSeeInOrder(['Dia', 'Pedidos', 'Faturamento', 'Ticket médio', 'Desconto']);
        $response->assertSee('10/08/2026');
        $response->assertSee('09/08/2026');
        $response->assertSee('08/08/2026');

        $response->assertSee('Exportar CSV');
        $response->assertSee('Exportar PDF');
    }

    public function test_example_base_de_renovacao_renders_at_least_three_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Base de renovação');
        $response->assertSeeInOrder(['Titular', 'Documento', 'Produto', 'Vence em', 'Dias', 'Contato']);
        $response->assertSee('Ana Beatriz Ramos');
        $response->assertSee('Carlos Eduardo Nogueira');
        $response->assertSee('Patrícia Gomes Vieira');
        $response->assertSee('WhatsApp');
    }
}
