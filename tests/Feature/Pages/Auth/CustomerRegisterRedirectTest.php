<?php

namespace Tests\Feature\Pages\Auth;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerRegisterRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function validFields(): array
    {
        return [
            'name' => 'Cliente Teste',
            'email' => 'cliente.teste@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ];
    }

    public function test_register_without_a_cart_intent_redirects_to_the_account_dashboard(): void
    {
        Livewire::test('pages::auth.customer.register')
            ->set($this->validFields())
            ->call('register')
            ->assertRedirect(route('minha-conta.pedidos'));
    }

    public function test_register_with_a_cart_intent_redirects_to_the_cart(): void
    {
        Livewire::test('pages::auth.customer.register')
            ->set('from', 'carrinho')
            ->set($this->validFields())
            ->call('register')
            ->assertRedirect(route('carrinho'));
    }

    public function test_visiting_the_register_page_with_a_cart_from_query_string_chains_it_into_the_login_link(): void
    {
        $response = $this->get(route('customer.register', ['from' => 'carrinho']));

        $response->assertOk();
        $response->assertSee(route('customer.login', ['from' => 'carrinho']), false);
    }
}
