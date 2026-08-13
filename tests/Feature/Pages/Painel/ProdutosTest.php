<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutosTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/produtos/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_produtos_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/produtos/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.produtos');
    }

    public function test_table_lista_renders_three_products_and_new_product_button(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/produtos/');

        $response->assertSeeInOrder(['Produto', 'Tipo', 'Slug', 'Variantes', 'A partir de', 'Ativo']);
        $response->assertSee('e-CPF');
        $response->assertSee('e-CNPJ');
        $response->assertSee('Certificado Digital para MEI');
        $response->assertSee('Novo produto');
    }

    public function test_block_edicao_produto_renders_six_prefilled_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/produtos/');

        $response->assertSee('Edição · dados do produto');
        $response->assertSee('value="e-CPF"', false);
        $response->assertSee('value="certificado-digital/e-cpf"', false);
        $response->assertSee('Tipo de titular');
        $response->assertSee('value="1"', false);
        $response->assertSee('value="Certificado Digital e-CPF para pessoa física"', false);
        $response->assertSee('checked', false);
    }

    public function test_table_variantes_renders_three_variants_with_eight_columns_and_new_variant_button(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/produtos/');

        $response->assertSeeInOrder(['SKU', 'Tipo', 'Validade', 'Preço', 'Promocional', 'Vigência', 'Padrão', 'Ativo']);
        $response->assertSee('ECPF-A1-12M');
        $response->assertSee('ECPF-A3-36M');
        $response->assertSee('ECPF-A1-24M');
        $response->assertSee('Nova variante');
    }

    public function test_block_edicao_variante_renders_eight_prefilled_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/produtos/');

        $response->assertSee('Edição de variante');
        $response->assertSee('value="ECPF-A1-12M"', false);
        $response->assertSee('Tipo de certificado');
        $response->assertSee('Validade em meses');
        $response->assertSee('value="R$ 250,00"', false);
        $response->assertSee('value="R$ 213,75"', false);
        $response->assertSee('value="até 31/08/2026"', false);
        $response->assertSee('Variante padrão');
    }
}
