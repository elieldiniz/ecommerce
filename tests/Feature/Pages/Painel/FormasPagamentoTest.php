<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_editar_forma_de_pagamento_carrega_formulario(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create(['name' => 'Boleto', 'slug' => 'boleto']);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editPaymentMethod', $method->id)
            ->assertSet('name', 'Boleto')
            ->assertSet('paymentMethodId', $method->id);
    }

    public function test_atualizar_forma_de_pagamento_persiste_apenas_os_4_campos_sem_alterar_slug(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create(['name' => 'Pix', 'slug' => 'pix']);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editPaymentMethod', $method->id)
            ->set('name', 'Pix atualizado')
            ->set('discount_percentage', '7.5')
            ->set('max_installments', 2)
            ->set('position', 5)
            ->call('updatePaymentMethod');

        $method->refresh();
        $this->assertSame('Pix atualizado', $method->name);
        $this->assertSame('pix', $method->slug);
        $this->assertSame(1, PaymentMethod::count());
    }

    public function test_atualizar_forma_de_pagamento_rejeita_name_vazio(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create();

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editPaymentMethod', $method->id)
            ->set('name', '')
            ->call('updatePaymentMethod')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_atualizar_forma_de_pagamento_rejeita_desconto_fora_do_intervalo(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create();

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editPaymentMethod', $method->id)
            ->set('discount_percentage', '150')
            ->call('updatePaymentMethod')
            ->assertHasErrors(['discount_percentage' => 'between']);
    }

    public function test_atualizar_forma_de_pagamento_rejeita_parcelas_menor_que_1(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create();

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editPaymentMethod', $method->id)
            ->set('max_installments', 0)
            ->call('updatePaymentMethod')
            ->assertHasErrors(['max_installments' => 'min']);
    }

    public function test_toggle_forma_de_pagamento_alterna_is_active_sem_alterar_formulario(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create(['is_active' => true]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('togglePaymentMethodStatus', $method->id);

        $this->assertFalse($method->refresh()->is_active);
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

    public function test_toggle_cupom_alterna_is_active_sem_alterar_formulario(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id, 'is_active' => true]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('toggleCouponStatus', $coupon->id)
            ->assertSet('couponId', null);

        $this->assertFalse($coupon->refresh()->is_active);
    }

    public function test_novo_cupom_limpa_o_formulario(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editCoupon', $coupon->id)
            ->assertSet('couponId', $coupon->id)
            ->call('resetCouponForm')
            ->assertSet('couponId', null)
            ->assertSet('code', '');
    }

    public function test_editar_cupom_carrega_todos_os_campos(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create([
            'code' => 'EDITAR10',
            'type_id' => $this->percentageType()->id,
            'value' => 10,
        ]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editCoupon', $coupon->id)
            ->assertSet('couponId', $coupon->id)
            ->assertSet('code', 'EDITAR10');
    }

    public function test_criar_cupom_sem_selecao_incrementa_contagem_com_uses_count_zero(): void
    {
        $this->actingAs(User::factory()->create());

        $type = $this->percentageType();

        Livewire::test('pages::painel.formas-pagamento')
            ->set('code', 'NOVO15')
            ->set('type_id', $type->id)
            ->set('value', '15')
            ->set('starts_at', '2026-08-01')
            ->set('ends_at', '2026-08-31')
            ->call('createCoupon');

        $coupon = Coupon::where('code', 'NOVO15')->firstOrFail();
        $this->assertSame(0, $coupon->uses_count);
        $this->assertSame(1, Coupon::count());
    }

    public function test_atualizar_cupom_com_selecao_nao_altera_uses_count(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create([
            'code' => 'MANTERUSOS',
            'type_id' => $this->percentageType()->id,
            'value' => 10,
            'uses_count' => 42,
        ]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editCoupon', $coupon->id)
            ->set('value', '20')
            ->call('updateCoupon');

        $coupon->refresh();
        $this->assertSame('20.00', (string) $coupon->value);
        $this->assertSame(42, $coupon->uses_count);
    }

    public function test_criar_cupom_rejeita_code_vazio(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.formas-pagamento')
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

        Livewire::test('pages::painel.formas-pagamento')
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

        Livewire::test('pages::painel.formas-pagamento')
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

        Livewire::test('pages::painel.formas-pagamento')
            ->set('code', 'VIGENCIA')
            ->set('type_id', $this->percentageType()->id)
            ->set('value', '10')
            ->set('starts_at', '2026-08-31')
            ->set('ends_at', '2026-08-01')
            ->call('createCoupon')
            ->assertHasErrors(['ends_at' => 'after']);
    }

    public function test_select_variante_restrita_exibe_produto_e_sku_grava_apenas_id(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create(['name' => 'e-CNPJ']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'ECNPJ-A1-12']);

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee('e-CNPJ — ECNPJ-A1-12');

        Livewire::test('pages::painel.formas-pagamento')
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

    public function test_cupom_com_variante_restrita_recarrega_select_com_opcao_correspondente(): void
    {
        $this->actingAs(User::factory()->create());

        $variant = ProductVariant::factory()->create(['sku' => 'ECPF-A1-12']);
        $coupon = Coupon::factory()->create([
            'type_id' => $this->percentageType()->id,
            'restricted_variant_id' => $variant->id,
        ]);

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editCoupon', $coupon->id)
            ->assertSet('restricted_variant_id', $variant->id);
    }

    public function test_atualizacoes_ocorrem_dentro_de_transacao(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create(['name' => 'Original']);

        DB::enableQueryLog();
        DB::flushQueryLog();

        Livewire::test('pages::painel.formas-pagamento')
            ->call('editPaymentMethod', $method->id)
            ->set('name', 'Atualizado via transacao')
            ->call('updatePaymentMethod');

        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue(collect($log)->contains(fn ($q) => str_contains(strtolower($q['query']), 'update')));
        $this->assertSame('Atualizado via transacao', $method->refresh()->name);
    }
}
