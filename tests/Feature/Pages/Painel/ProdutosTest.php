<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\CertificateFormat;
use App\Models\HolderType;
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

    public function test_block_edicao_produto_renders_the_form(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/produtos/');

        $response->assertSee('Edição · dados do produto');
        $response->assertSee('Tipo de titular');
    }

    public function test_creating_product_with_valid_data_creates_exactly_one_active_row(): void
    {
        $this->actingAs(User::factory()->create());

        $holderType = HolderType::factory()->create();

        Livewire::test('pages::painel.produtos')
            ->set('name', 'Certificado Digital e-CPF')
            ->set('slug', 'certificado-digital-e-cpf')
            ->set('holder_type_id', $holderType->id)
            ->set('position', 1)
            ->call('createProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'name' => 'Certificado Digital e-CPF',
            'slug' => 'certificado-digital-e-cpf',
            'holder_type_id' => $holderType->id,
            'position' => 1,
            'is_active' => true,
        ]);
    }

    public function test_creating_product_with_duplicate_slug_is_rejected_without_creating_row(): void
    {
        $this->actingAs(User::factory()->create());

        $holderType = HolderType::factory()->create();
        $existing = Product::factory()->create(['slug' => 'certificado-digital-e-cpf', 'holder_type_id' => $holderType->id]);

        Livewire::test('pages::painel.produtos')
            ->set('name', 'Outro produto')
            ->set('slug', 'certificado-digital-e-cpf')
            ->set('holder_type_id', $holderType->id)
            ->set('position', 1)
            ->call('createProduct')
            ->assertHasErrors(['slug' => 'unique']);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $existing->id, 'slug' => 'certificado-digital-e-cpf']);
    }

    public function test_creating_product_without_required_fields_is_rejected_per_field(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.produtos')
            ->set('name', '')
            ->set('slug', '')
            ->set('holder_type_id', null)
            ->set('position', null)
            ->call('createProduct')
            ->assertHasErrors(['name' => 'required', 'slug' => 'required', 'holder_type_id' => 'required', 'position' => 'required']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_editing_product_reflects_in_listing(): void
    {
        $this->actingAs(User::factory()->create());

        $holderType = HolderType::factory()->create();
        $product = Product::factory()->create(['name' => 'Nome antigo', 'holder_type_id' => $holderType->id]);

        Livewire::test('pages::painel.produtos')
            ->call('editProduct', $product->id)
            ->set('name', 'Nome novo')
            ->set('slug', 'novo-slug')
            ->set('holder_type_id', $holderType->id)
            ->set('position', 5)
            ->call('updateProduct')
            ->assertHasNoErrors();

        $response = $this->get('/painel/produtos/');
        $response->assertSee('Nome novo');
        $response->assertDontSee('Nome antigo');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nome novo',
            'slug' => 'novo-slug',
            'holder_type_id' => $holderType->id,
            'position' => 5,
        ]);
    }

    public function test_editing_product_slug_conflict_is_rejected_and_original_unchanged(): void
    {
        $this->actingAs(User::factory()->create());

        $other = Product::factory()->create(['slug' => 'slug-em-uso']);
        $product = Product::factory()->create(['name' => 'Produto original', 'slug' => 'slug-original']);

        Livewire::test('pages::painel.produtos')
            ->call('editProduct', $product->id)
            ->set('slug', 'slug-em-uso')
            ->call('updateProduct')
            ->assertHasErrors(['slug' => 'unique']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto original',
            'slug' => 'slug-original',
        ]);
        $this->assertDatabaseHas('products', ['id' => $other->id, 'slug' => 'slug-em-uso']);
    }

    public function test_toggling_product_status_persists_product_and_variants_and_flips_status(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create(['is_active' => true]);
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $a3 = CertificateFormat::query()->firstOrCreate(['slug' => 'a3'], ['name' => 'A3', 'requires_hardware' => true]);
        $variants = collect([
            ProductVariant::factory()->for($product)->create(['certificate_format_id' => $a1->id]),
            ProductVariant::factory()->for($product)->create(['certificate_format_id' => $a3->id]),
        ]);

        Livewire::test('pages::painel.produtos')
            ->call('toggleProductStatus', $product->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        foreach ($variants as $variant) {
            $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'product_id' => $product->id]);
        }

        $response = $this->get('/painel/produtos/');
        $response->assertSee('Ativar');
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
