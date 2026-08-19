<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FormasPagamentoShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $method = PaymentMethod::factory()->create();

        $response = $this->get("/painel/formas-pagamento/{$method->id}/");

        $response->assertRedirect(route('login'));
    }

    public function test_id_inexistente_retorna_404(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/formas-pagamento/999999/');

        $response->assertNotFound();
    }

    public function test_mount_pre_carrega_os_4_campos(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create([
            'name' => 'Cartão de crédito',
            'discount_percentage' => 5,
            'max_installments' => 12,
            'position' => 2,
        ]);

        Livewire::test('pages::painel.formas-pagamento.show', ['id' => $method->id])
            ->assertSet('name', 'Cartão de crédito')
            ->assertSet('discount_percentage', '5.00')
            ->assertSet('max_installments', 12)
            ->assertSet('position', 2);
    }

    public function test_atualizar_persiste_apenas_os_4_campos_sem_alterar_slug_e_redireciona(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create(['name' => 'Pix', 'slug' => 'pix']);

        Livewire::test('pages::painel.formas-pagamento.show', ['id' => $method->id])
            ->set('name', 'Pix atualizado')
            ->set('discount_percentage', '7.5')
            ->set('max_installments', 2)
            ->set('position', 5)
            ->call('updatePaymentMethod')
            ->assertRedirect(route('painel.formas-pagamento'));

        $method->refresh();
        $this->assertSame('Pix atualizado', $method->name);
        $this->assertSame('pix', $method->slug);
    }

    public function test_atualizar_rejeita_name_vazio(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create();

        Livewire::test('pages::painel.formas-pagamento.show', ['id' => $method->id])
            ->set('name', '')
            ->call('updatePaymentMethod')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_atualizar_rejeita_desconto_fora_do_intervalo(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create();

        Livewire::test('pages::painel.formas-pagamento.show', ['id' => $method->id])
            ->set('discount_percentage', '150')
            ->call('updatePaymentMethod')
            ->assertHasErrors(['discount_percentage' => 'between']);
    }

    public function test_atualizar_rejeita_parcelas_menor_que_1(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create();

        Livewire::test('pages::painel.formas-pagamento.show', ['id' => $method->id])
            ->set('max_installments', 0)
            ->call('updatePaymentMethod')
            ->assertHasErrors(['max_installments' => 'min']);
    }

    public function test_atualizacao_ocorre_dentro_de_transacao(): void
    {
        $this->actingAs(User::factory()->create());

        $method = PaymentMethod::factory()->create(['name' => 'Original']);

        DB::enableQueryLog();
        DB::flushQueryLog();

        Livewire::test('pages::painel.formas-pagamento.show', ['id' => $method->id])
            ->set('name', 'Atualizado via transacao')
            ->call('updatePaymentMethod');

        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue(collect($log)->contains(fn ($q) => str_contains(strtolower($q['query']), 'update')));
        $this->assertSame('Atualizado via transacao', $method->refresh()->name);
    }
}
