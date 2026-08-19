<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormasPagamentoCuponsCreateTest extends TestCase
{
    use RefreshDatabase;

    private function percentageType(): CouponType
    {
        return CouponType::query()->firstOrCreate(['slug' => 'percentage'], ['name' => 'Percentual']);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/formas-pagamento/cupons/novo/');

        $response->assertRedirect(route('login'));
    }

    public function test_pagina_carrega_com_campos_vazios(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.formas-pagamento.cupons.create')
            ->assertOk()
            ->assertSet('code', '')
            ->assertSet('type_id', null)
            ->assertSet('restricted_variant_id', null);
    }

    public function test_criar_cupom_sem_selecao_incrementa_contagem_com_uses_count_zero_e_redireciona(): void
    {
        $this->actingAs(User::factory()->create());

        $type = $this->percentageType();

        Livewire::test('pages::painel.formas-pagamento.cupons.create')
            ->set('code', 'NOVO15')
            ->set('type_id', $type->id)
            ->set('value', '15')
            ->set('starts_at', '2026-08-01')
            ->set('ends_at', '2026-08-31')
            ->call('createCoupon')
            ->assertRedirect(route('painel.formas-pagamento'));

        $coupon = Coupon::where('code', 'NOVO15')->firstOrFail();
        $this->assertSame(0, $coupon->uses_count);
        $this->assertSame(1, Coupon::count());
    }

    public function test_select_variante_restrita_exibe_produto_e_sku_grava_apenas_id(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create(['name' => 'e-CNPJ']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'ECNPJ-A1-12']);

        $response = $this->get('/painel/formas-pagamento/cupons/novo/');
        $response->assertSee('e-CNPJ — ECNPJ-A1-12');

        Livewire::test('pages::painel.formas-pagamento.cupons.create')
            ->set('code', 'RESTRITO')
            ->set('type_id', $this->percentageType()->id)
            ->set('value', '10')
            ->set('restricted_variant_id', (string) $variant->id)
            ->set('starts_at', '2026-08-01')
            ->set('ends_at', '2026-08-31')
            ->call('createCoupon');

        $coupon = Coupon::where('code', 'RESTRITO')->firstOrFail();
        $this->assertSame($variant->id, $coupon->restricted_variant_id);
    }

    public function test_criar_cupom_rejeita_code_vazio(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.formas-pagamento.cupons.create')
            ->set('code', '')
            ->set('type_id', $this->percentageType()->id)
            ->set('value', '10')
            ->set('starts_at', '2026-08-01')
            ->set('ends_at', '2026-08-31')
            ->call('createCoupon')
            ->assertHasErrors(['code' => 'required']);
    }

    public function test_criar_cupom_rejeita_code_duplicado(): void
    {
        $this->actingAs(User::factory()->create());

        Coupon::factory()->create(['code' => 'DUPLICADO', 'type_id' => $this->percentageType()->id]);

        Livewire::test('pages::painel.formas-pagamento.cupons.create')
            ->set('code', 'DUPLICADO')
            ->set('type_id', $this->percentageType()->id)
            ->set('value', '10')
            ->set('starts_at', '2026-08-01')
            ->set('ends_at', '2026-08-31')
            ->call('createCoupon')
            ->assertHasErrors(['code' => 'unique']);
    }

    public function test_criar_cupom_rejeita_value_menor_ou_igual_a_zero(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.formas-pagamento.cupons.create')
            ->set('code', 'VALORZERO')
            ->set('type_id', $this->percentageType()->id)
            ->set('value', '0')
            ->set('starts_at', '2026-08-01')
            ->set('ends_at', '2026-08-31')
            ->call('createCoupon')
            ->assertHasErrors(['value' => 'min']);
    }

    public function test_criar_cupom_rejeita_vigencia_invertida(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.formas-pagamento.cupons.create')
            ->set('code', 'VIGENCIA')
            ->set('type_id', $this->percentageType()->id)
            ->set('value', '10')
            ->set('starts_at', '2026-08-31')
            ->set('ends_at', '2026-08-01')
            ->call('createCoupon')
            ->assertHasErrors(['ends_at' => 'after']);
    }
}
