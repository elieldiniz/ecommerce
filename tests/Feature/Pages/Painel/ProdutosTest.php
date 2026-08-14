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

        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $a3 = CertificateFormat::query()->firstOrCreate(['slug' => 'a3'], ['name' => 'A3', 'requires_hardware' => true]);

        ProductVariant::factory()->for($product)->create([
            'certificate_format_id' => $a1->id,
            'sku' => 'PRODUTO-A1-VARIANTE',
            'validity_months' => 12,
            'price' => 250,
            'is_active' => true,
        ]);
        ProductVariant::factory()->for($product)->create([
            'certificate_format_id' => $a3->id,
            'sku' => 'PRODUTO-A3-VARIANTE',
            'validity_months' => 36,
            'price' => 350,
            'is_active' => false,
        ]);
        ProductVariant::factory()->for($otherProduct)->create([
            'certificate_format_id' => $a1->id,
            'sku' => 'OUTRO-PRODUTO-SKU',
        ]);

        $component = Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id);

        $component->assertSeeHtmlInOrder(['SKU', 'Tipo', 'Validade', 'Preço', 'Promocional', 'Vigência', 'Padrão', 'Ativo']);
        $component->assertSee('Nova variante');
        $component->assertSee('PRODUTO-A1-VARIANTE');
        $component->assertSee('PRODUTO-A3-VARIANTE');
        $component->assertDontSee('OUTRO-PRODUTO-SKU');

        $component->call('selectProduct', $otherProduct->id)
            ->assertSee('OUTRO-PRODUTO-SKU')
            ->assertDontSee('PRODUTO-A1-VARIANTE')
            ->assertDontSee('PRODUTO-A3-VARIANTE');
    }

    public function test_block_edicao_variante_renders_the_form(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/produtos/');

        $response->assertSee('Edição de variante');
        $response->assertSee('Tipo de certificado');
        $response->assertSee('Validade em meses');
        $response->assertSee('Preço promocional');
        $response->assertSee('Início da vigência da promoção');
        $response->assertSee('Fim da vigência da promoção');
    }

    public function test_creating_variant_with_valid_data_creates_exactly_one_row_for_selected_product(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);

        Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->set('sku', 'ECPF-A1-12M')
            ->set('certificate_format_id', $a1->id)
            ->set('validity_months', 12)
            ->set('price', '250.00')
            ->call('createVariant')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('product_variants', 1);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'ECPF-A1-12M',
            'certificate_format_id' => $a1->id,
            'validity_months' => 12,
        ]);
    }

    public function test_creating_variant_with_duplicate_sku_is_rejected_without_creating_row(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $a3 = CertificateFormat::query()->firstOrCreate(['slug' => 'a3'], ['name' => 'A3', 'requires_hardware' => true]);

        $existing = ProductVariant::factory()->for($otherProduct)->create(['sku' => 'DUPLICADO-SKU', 'certificate_format_id' => $a1->id]);

        Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->set('sku', 'DUPLICADO-SKU')
            ->set('certificate_format_id', $a3->id)
            ->set('validity_months', 12)
            ->set('price', '250.00')
            ->call('createVariant')
            ->assertHasErrors(['sku' => 'unique']);

        $this->assertDatabaseCount('product_variants', 1);
        $this->assertDatabaseHas('product_variants', ['id' => $existing->id, 'sku' => 'DUPLICADO-SKU']);
    }

    public function test_creating_variant_without_required_fields_is_rejected_per_field(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();

        Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->set('sku', '')
            ->set('certificate_format_id', null)
            ->set('price', null)
            ->call('createVariant')
            ->assertHasErrors(['sku' => 'required', 'certificate_format_id' => 'required', 'price' => 'required']);

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_creating_variant_with_promotional_price_without_promotion_window_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);

        Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->set('sku', 'ECPF-A1-12M')
            ->set('certificate_format_id', $a1->id)
            ->set('validity_months', 12)
            ->set('price', '250.00')
            ->set('promotional_price', '213.75')
            ->call('createVariant')
            ->assertHasErrors(['promotion_starts_at' => 'required_with', 'promotion_ends_at' => 'required_with']);

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_creating_second_variant_of_same_format_for_same_product_is_rejected_as_form_error(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        ProductVariant::factory()->for($product)->create(['certificate_format_id' => $a1->id]);

        Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->set('sku', 'SEGUNDA-VARIANTE-A1')
            ->set('certificate_format_id', $a1->id)
            ->set('validity_months', 12)
            ->set('price', '250.00')
            ->call('createVariant')
            ->assertHasErrors(['certificate_format_id' => 'unique']);

        $this->assertDatabaseCount('product_variants', 1);
    }

    public function test_editing_variant_reflects_in_variant_listing(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'SKU-ANTIGO', 'certificate_format_id' => $a1->id]);

        Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->call('editVariant', $variant->id)
            ->set('sku', 'SKU-NOVO')
            ->call('updateVariant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'SKU-NOVO']);

        $component = Livewire::test('pages::painel.produtos')->call('selectProduct', $product->id);
        $component->assertSee('SKU-NOVO');
        $component->assertDontSee('SKU-ANTIGO');
    }

    public function test_editing_variant_sku_conflict_is_rejected_and_original_unchanged(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $a3 = CertificateFormat::query()->firstOrCreate(['slug' => 'a3'], ['name' => 'A3', 'requires_hardware' => true]);

        $other = ProductVariant::factory()->for($product)->create(['sku' => 'SKU-EM-USO', 'certificate_format_id' => $a1->id]);
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'SKU-ORIGINAL', 'certificate_format_id' => $a3->id]);

        Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->call('editVariant', $variant->id)
            ->set('sku', 'SKU-EM-USO')
            ->call('updateVariant')
            ->assertHasErrors(['sku' => 'unique']);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'sku' => 'SKU-ORIGINAL']);
        $this->assertDatabaseHas('product_variants', ['id' => $other->id, 'sku' => 'SKU-EM-USO']);
    }

    public function test_setting_default_variant_unsets_others_from_same_product_and_ignores_other_products(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $a3 = CertificateFormat::query()->firstOrCreate(['slug' => 'a3'], ['name' => 'A3', 'requires_hardware' => true]);

        $variantA = ProductVariant::factory()->for($product)->create(['certificate_format_id' => $a1->id, 'is_default' => true]);
        $variantB = ProductVariant::factory()->for($product)->create(['certificate_format_id' => $a3->id, 'is_default' => false]);
        $variantC = ProductVariant::factory()->for($product)->create(['certificate_format_id' => CertificateFormat::query()->firstOrCreate(['slug' => 'a1-extra'], ['name' => 'A1 Extra', 'requires_hardware' => false])->id, 'is_default' => false]);
        $otherProductVariant = ProductVariant::factory()->for($otherProduct)->create(['certificate_format_id' => $a1->id, 'is_default' => true]);

        Livewire::test('pages::painel.produtos')
            ->call('setDefaultVariant', $variantB->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_variants', ['id' => $variantA->id, 'is_default' => false]);
        $this->assertDatabaseHas('product_variants', ['id' => $variantB->id, 'is_default' => true]);
        $this->assertDatabaseHas('product_variants', ['id' => $variantC->id, 'is_default' => false]);
        $this->assertDatabaseHas('product_variants', ['id' => $otherProductVariant->id, 'is_default' => true]);
    }

    public function test_rnf01_variant_creation_write_is_immediately_reflected_in_same_request_variant_count(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);

        $component = Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->set('sku', 'RNF01-A1-VARIANTE')
            ->set('certificate_format_id', $a1->id)
            ->set('validity_months', 12)
            ->set('price', '250.00')
            ->call('createVariant')
            ->assertHasNoErrors();

        $reloadedProduct = $component->instance()->products()->firstWhere('id', $product->id);

        $this->assertSame(1, $reloadedProduct->variants->count());
    }

    public function test_rnf02_duplicate_variant_format_shows_portuguese_form_error_without_leaking_database_exception(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create();
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        ProductVariant::factory()->for($product)->create(['certificate_format_id' => $a1->id]);

        $component = Livewire::test('pages::painel.produtos')
            ->call('selectProduct', $product->id)
            ->set('sku', 'RNF02-SEGUNDA-A1')
            ->set('certificate_format_id', $a1->id)
            ->set('validity_months', 12)
            ->set('price', '250.00')
            ->call('createVariant');

        $component->assertHasErrors(['certificate_format_id' => 'unique']);
        $component->assertSee('Este produto já possui uma variante com este formato.');
        $component->assertDontSee('SQLSTATE');
        $component->assertDontSee('QueryException');

        $this->assertDatabaseCount('product_variants', 1);
    }
}
