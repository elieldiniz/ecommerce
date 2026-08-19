<?php

namespace Tests\Feature\Pages\MinhaConta;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DadosTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(): Customer
    {
        return Customer::factory()->create([
            'role_id' => null,
        ]);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/minha-conta/dados/');

        $response->assertRedirect();
    }

    public function test_authenticated_route_renders_view(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/dados/');

        $response->assertOk();
    }

    public function test_displays_profile_form(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/dados/');

        $response->assertOk();
        $response->assertSee('Dados pessoais');
        $response->assertSee('Nome completo');
        $response->assertSee('E-mail');
        $response->assertSee('Telefone');
        $response->assertSee('Salvar alterações');
    }

    public function test_displays_account_info_section(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/dados/');

        $response->assertOk();
        $response->assertSee('Informações da conta');
        $response->assertSee('Tipo de pessoa');
        $response->assertSee('Documento (CPF/CNPJ)');
    }

    public function test_sidebar_links_are_present(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/dados/');

        $response->assertOk();
        $response->assertSee('Meus pedidos');
        $response->assertSee('Meus dados');
        $response->assertSee('Trocar senha');
    }
}
