<?php

namespace Tests\Feature\Pages\MinhaConta;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SenhaTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(): Customer
    {
        return Customer::factory()->create([
            'role_id' => null,
            'password' => 'password123',
        ]);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/minha-conta/senha/');

        $response->assertRedirect();
    }

    public function test_authenticated_route_renders_view(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/senha/');

        $response->assertOk();
    }

    public function test_displays_password_form(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/senha/');

        $response->assertOk();
        $response->assertSee('Alterar senha');
        $response->assertSee('Senha atual');
        $response->assertSee('Nova senha');
        $response->assertSee('Confirmar nova senha');
    }

    public function test_sidebar_links_are_present(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/senha/');

        $response->assertOk();
        $response->assertSee('Meus pedidos');
        $response->assertSee('Meus dados');
        $response->assertSee('Trocar senha');
    }
}
