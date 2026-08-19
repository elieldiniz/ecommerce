<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormasPagamentoTest extends TestCase
{
    use RefreshDatabase;

    private function percentageType(): CouponType
    {
        return CouponType::query()->firstOrCreate(['slug' => 'percentage'], ['name' => 'Percentual']);
    }

    private function fixedAmountType(): CouponType
    {
        return CouponType::query()->firstOrCreate(['slug' => 'fixed_amount'], ['name' => 'Valor fixo']);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/formas-pagamento/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_view(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.formas-pagamento')->assertOk();
    }

    public function test_table_formas_pagamento_le_dados_reais(): void
    {
        $this->actingAs(User::factory()->create());

        PaymentMethod::factory()->create(['name' => 'Pix', 'slug' => 'pix', 'position' => 1]);
        PaymentMethod::factory()->create(['name' => 'Cartão de crédito', 'slug' => 'cartao', 'position' => 2]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertOk();
        $response->assertSeeInOrder(['pix', 'cartao']);
    }

    public function test_toggle_forma_de_pagamento_alterna_is_active_sem_navegar(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create(['is_active' => true]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('togglePaymentMethodStatus', $method->id)
            ->assertOk();

        $this->assertFalse($method->refresh()->is_active);
    }

    public function test_linha_de_forma_de_pagamento_tem_link_editar_para_pagina_dedicada(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create();

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee(route('painel.formas-pagamento.show', $method->id), false);
    }

    public function test_table_cupons_le_dados_reais_com_type_e_restricted_variant(): void
    {
        $this->actingAs(User::factory()->create());

        $variant = ProductVariant::factory()->create(['sku' => 'ECNPJ-A1-12']);
        Coupon::factory()->create([
            'code' => 'ECNPJ25',
            'type_id' => $this->percentageType()->id,
            'restricted_variant_id' => $variant->id,
        ]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertOk();
        $response->assertSee('ECNPJ25');
        $response->assertSee('ECNPJ-A1-12');
    }

    public function test_cupom_percentual_exibe_sufixo_percentual(): void
    {
        $this->actingAs(User::factory()->create());

        Coupon::factory()->create(['code' => 'PERC10', 'type_id' => $this->percentageType()->id, 'value' => 10]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee('10,00%');
    }

    public function test_cupom_valor_fixo_exibe_em_reais(): void
    {
        $this->actingAs(User::factory()->create());

        Coupon::factory()->create(['code' => 'FIXO50', 'type_id' => $this->fixedAmountType()->id, 'value' => 50]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee('R$');
        $response->assertSee('50,00');
    }

    public function test_cupom_sem_limite_de_uso_exibe_sem_limite(): void
    {
        $this->actingAs(User::factory()->create());

        Coupon::factory()->create(['code' => 'SEMLIMITE', 'type_id' => $this->percentageType()->id, 'usage_limit' => null]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee('Sem limite');
    }

    public function test_cupom_sem_restricao_exibe_todas_as_variantes(): void
    {
        $this->actingAs(User::factory()->create());

        Coupon::factory()->create(['code' => 'GERAL10', 'type_id' => $this->percentageType()->id, 'restricted_variant_id' => null]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee('Todas as variantes');
    }

    public function test_toggle_cupom_alterna_is_active_sem_navegar(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id, 'is_active' => true]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('toggleCouponStatus', $coupon->id)
            ->assertOk();

        $this->assertFalse($coupon->refresh()->is_active);
    }

    public function test_botao_novo_cupom_aponta_para_pagina_dedicada(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee(route('painel.formas-pagamento.cupons.create'), false);
    }

    public function test_linha_de_cupom_tem_link_editar_para_pagina_dedicada(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee(route('painel.formas-pagamento.cupons.show', $coupon->id), false);
    }

    public function test_pagina_nao_contem_nenhum_formulario_embutido(): void
    {
        $this->actingAs(User::factory()->create());

        PaymentMethod::factory()->create();
        Coupon::factory()->create(['type_id' => $this->percentageType()->id]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertDontSee('Edição de cupom');
        $response->assertDontSee('name="wire:model"', false);
        $this->assertSame(0, substr_count($response->getContent(), '<form'));
    }

    public function test_select_variante_restrita_exibe_produto_e_sku_na_lista(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create(['name' => 'e-CNPJ']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'ECNPJ-A1-12']);
        Coupon::factory()->create([
            'code' => 'RESTRITO',
            'type_id' => $this->percentageType()->id,
            'restricted_variant_id' => $variant->id,
        ]);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee('ECNPJ-A1-12');
    }
}
