<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
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

        Livewire::test('pages::painel.produtos')
            ->assertOk();
    }

    public function test_table_lista_renders_three_products_and_new_product_button(): void
    {
        $this->actingAs(User::factory()->create());

        $withoutVariant = Product::factory()->create(['name' => 'Sem Variante']);

        $withActivePromotion = Product::factory()->create(['name' => 'Com Promoção Vigente']);
        ProductVariant::factory()->for($withActivePromotion)->create([
            'price' => 300,
            'promotional_price' => 213.75,
            'promotion_starts_at' => now()->subDay(),
            'promotion_ends_at' => now()->addDay(),
        ]);

        $withoutActivePromotion = Product::factory()->create(['name' => 'Sem Promoção Vigente', 'is_active' => false]);
        ProductVariant::factory()->for($withoutActivePromotion)->create([
            'price' => 250,
            'promotional_price' => 190,
            'promotion_starts_at' => now()->subDays(10),
            'promotion_ends_at' => now()->subDays(5),
        ]);

        $response = $this->get('/painel/produtos/');

        $response->assertSeeInOrder(['Produto', 'Tipo', 'Slug', 'Variantes', 'A partir de', 'Ativo']);
        $response->assertSee('Novo produto');

        $response->assertSee('Sem Variante');
        $response->assertSee('—');

        $response->assertSee('Com Promoção Vigente');
        $response->assertSee(Number::currency(213.75, in: 'BRL', locale: 'pt_BR'));

        $response->assertSee('Sem Promoção Vigente');
        $response->assertSee(Number::currency(250, in: 'BRL', locale: 'pt_BR'));
        $response->assertSee('Não');
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
