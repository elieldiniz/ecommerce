<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FormasPagamentoCuponsShowTest extends TestCase
{
    use RefreshDatabase;

    private function percentageType(): CouponType
    {
        return CouponType::query()->firstOrCreate(['slug' => 'percentage'], ['name' => 'Percentual']);
    }

    public function test_route_requires_authentication(): void
    {
        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id]);

        $response = $this->get("/painel/formas-pagamento/cupons/{$coupon->id}/");

        $response->assertRedirect(route('login'));
    }

    public function test_id_inexistente_retorna_404(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/formas-pagamento/cupons/999999/');

        $response->assertNotFound();
    }

    public function test_mount_pre_carrega_os_8_campos_incluindo_restricted_variant_id(): void
    {
        $this->actingAs(User::factory()->create());

        $variant = ProductVariant::factory()->create(['sku' => 'ECPF-A1-12']);
        $coupon = Coupon::factory()->create([
            'code' => 'EDITAR10',
            'type_id' => $this->percentageType()->id,
            'value' => 10,
            'usage_limit' => 500,
            'per_customer_limit' => 1,
            'restricted_variant_id' => $variant->id,
        ]);

        Livewire::test('pages::painel.formas-pagamento.cupons.show', ['id' => $coupon->id])
            ->assertSet('code', 'EDITAR10')
            ->assertSet('type_id', $this->percentageType()->id)
            ->assertSet('value', '10.00')
            ->assertSet('usage_limit', 500)
            ->assertSet('per_customer_limit', 1)
            ->assertSet('restricted_variant_id', $variant->id);
    }

    public function test_atualizar_com_selecao_nao_altera_uses_count_e_redireciona(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create([
            'code' => 'MANTERUSOS',
            'type_id' => $this->percentageType()->id,
            'value' => 10,
            'uses_count' => 42,
        ]);

        Livewire::test('pages::painel.formas-pagamento.cupons.show', ['id' => $coupon->id])
            ->set('value', '20')
            ->call('updateCoupon')
            ->assertRedirect(route('painel.formas-pagamento'));

        $coupon->refresh();
        $this->assertSame('20.00', (string) $coupon->value);
        $this->assertSame(42, $coupon->uses_count);
    }

    public function test_submeter_o_proprio_code_inalterado_nao_dispara_erro_de_unicidade(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['code' => 'MESMOCODE', 'type_id' => $this->percentageType()->id]);

        Livewire::test('pages::painel.formas-pagamento.cupons.show', ['id' => $coupon->id])
            ->set('value', '25')
            ->call('updateCoupon')
            ->assertHasNoErrors()
            ->assertRedirect(route('painel.formas-pagamento'));
    }

    public function test_criar_cupom_rejeita_code_vazio(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id]);

        Livewire::test('pages::painel.formas-pagamento.cupons.show', ['id' => $coupon->id])
            ->set('code', '')
            ->call('updateCoupon')
            ->assertHasErrors(['code' => 'required']);
    }

    public function test_criar_cupom_rejeita_code_duplicado(): void
    {
        $this->actingAs(User::factory()->create());

        Coupon::factory()->create(['code' => 'JAEXISTE', 'type_id' => $this->percentageType()->id]);
        $coupon = Coupon::factory()->create(['code' => 'OUTRO', 'type_id' => $this->percentageType()->id]);

        Livewire::test('pages::painel.formas-pagamento.cupons.show', ['id' => $coupon->id])
            ->set('code', 'JAEXISTE')
            ->call('updateCoupon')
            ->assertHasErrors(['code' => 'unique']);
    }

    public function test_criar_cupom_rejeita_value_menor_ou_igual_a_zero(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id]);

        Livewire::test('pages::painel.formas-pagamento.cupons.show', ['id' => $coupon->id])
            ->set('value', '0')
            ->call('updateCoupon')
            ->assertHasErrors(['value' => 'min']);
    }

    public function test_criar_cupom_rejeita_vigencia_invertida(): void
    {
        $this->actingAs(User::factory()->create());

        $coupon = Coupon::factory()->create(['type_id' => $this->percentageType()->id]);

        Livewire::test('pages::painel.formas-pagamento.cupons.show', ['id' => $coupon->id])
            ->set('starts_at', '2026-08-31')
            ->set('ends_at', '2026-08-01')
            ->call('updateCoupon')
            ->assertHasErrors(['ends_at' => 'after']);
    }
}
