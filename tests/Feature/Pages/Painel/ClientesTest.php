<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/clientes/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_clientes_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/clientes/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.clientes');
    }

    public function test_block_filtros_e_lista_renders_four_filters_search_and_three_customer_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/clientes/');

        $response->assertSee('Tipo de pessoa');
        $response->assertSee('UF');
        $response->assertSee('Período de cadastro');
        $response->assertSee('Com certificado vencendo');
        $response->assertSee('Buscar');

        $response->assertSeeInOrder(['Nome ou razão social', 'Documento', 'Tipo', 'E-mail', 'Pedidos', 'Última compra']);
        $response->assertSee('Maria Aparecida Souza');
        $response->assertSee('Comércio Digital Lock Ltda');
        $response->assertSee('Roberto Lima Castro');
    }

    public function test_block_ficha_renders_identification_fields_and_seven_independent_address_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/clientes/');

        $response->assertSee('Ficha do cliente · dados');
        $response->assertSee('Razão social / Nome');
        $response->assertSee('Documento');
        $response->assertSee('Telefone');

        $response->assertSee('CEP');
        $response->assertSee('Logradouro');
        $response->assertSee('Número');
        $response->assertSee('Complemento');
        $response->assertSee('Bairro');
        $response->assertSee('Município');
        $response->assertSee('UF');
    }

    public function test_table_historico_de_pedidos_renders_at_least_two_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/clientes/');

        $response->assertSee('Histórico de pedidos');
        $response->assertSeeInOrder(['Pedido', 'Produto', 'Valor', 'Pagamento', 'Emissão', 'Validade até']);
        $response->assertSee('#1042');
        $response->assertSee('#0988');
    }

    public function test_table_titulares_vinculados_renders_at_least_two_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/clientes/');

        $response->assertSee('Titulares vinculados');
        $response->assertSeeInOrder(['Titular', 'Documento', 'Tipo', 'Responsável', 'Certificado até']);
        $response->assertSee('João Pedro Almeida');
    }
}
