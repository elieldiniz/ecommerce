<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormasPagamentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/formas-pagamento/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_formas_pagamento_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertOk();
        $response->assertViewIs('pages.painel.formas-pagamento');
    }

    public function test_table_formas_pagamento_renders_exactly_three_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSeeInOrder(['Código', 'Nome exibido', 'Desconto', 'Máx. parcelas', 'Ordem', 'Ativo']);
        $response->assertSee('pix');
        $response->assertSee('cartao');
        $response->assertSee('boleto');
    }

    public function test_table_cupons_renders_at_least_three_rows_and_new_coupon_button(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSeeInOrder(['Código', 'Tipo', 'Valor', 'Usos', 'Limite', 'Vigência', 'Restrição', 'Ativo']);
        $response->assertSee('BEMVINDO10');
        $response->assertSee('ECNPJ25');
        $response->assertSee('PARCEIRO50');
        $response->assertSee('Novo cupom');
    }

    public function test_block_edicao_cupom_renders_nine_prefilled_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/formas-pagamento/');

        $response->assertSee('Edição de cupom');
        $response->assertSee('value="BEMVINDO10"', false);
        $response->assertSee('value="10%"', false);
        $response->assertSee('Limite de usos');
        $response->assertSee('Limite por cliente');
        $response->assertSee('Restrito à variante');
        $response->assertSee('Válido de');
        $response->assertSee('Válido até');
    }
}
