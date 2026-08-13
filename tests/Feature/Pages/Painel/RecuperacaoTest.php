<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecuperacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/recuperacao/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_recuperacao_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.recuperacao');
    }

    public function test_block_indicadores_renders_four_kpi_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('Pagos sem dados');
        $response->assertSee('Recuperados em 7 dias');
        $response->assertSee('Mais antigo');
        $response->assertSee('Falha de envio');
    }

    public function test_table_fila_renders_rows_ordered_by_dias_descending(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertSeeInOrder(['#1039', '#1044', '#1046', '#1048']);

        preg_match_all('/<tr>/', $response->getContent(), $rows);
        $this->assertGreaterThanOrEqual(4, count($rows[0]));
    }

    public function test_table_regua_automatica_renders_five_rows(): void
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

    public function test_table_falhas_de_integracao_renders_at_least_two_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('Falhas de integração');
        $response->assertSee('Corrigir e reenviar');
        $response->assertSee('#1038');
        $response->assertSee('#1035');
    }
}
