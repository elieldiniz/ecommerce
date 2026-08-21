<?php

namespace Tests\Feature\Pages\Auth;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_without_a_cart_intent_redirects_to_the_account_dashboard(): void
    {
        $customer = Customer::factory()->create(['password' => 'senha12345']);

        Livewire::test('pages::auth.customer.login')
            ->set('email', $customer->email)
            ->set('password', 'senha12345')
            ->call('login')
            ->assertRedirect(route('minha-conta.pedidos'));
    }

    public function test_login_with_a_cart_intent_redirects_to_the_cart(): void
    {
        $customer = Customer::factory()->create(['password' => 'senha12345']);

        Livewire::test('pages::auth.customer.login')
            ->set('from', 'carrinho')
            ->set('email', $customer->email)
            ->set('password', 'senha12345')
            ->call('login')
            ->assertRedirect(route('carrinho'));
    }

    public function test_an_unrecognized_from_value_is_ignored_and_redirects_to_the_account_dashboard(): void
    {
        $customer = Customer::factory()->create(['password' => 'senha12345']);

        Livewire::test('pages::auth.customer.login')
            ->set('from', 'https://malicious.example.com')
            ->set('email', $customer->email)
            ->set('password', 'senha12345')
            ->call('login')
            ->assertRedirect(route('minha-conta.pedidos'));
    }

    public function test_visiting_the_login_page_with_a_cart_from_query_string_chains_it_into_the_register_and_forgot_password_links(): void
    {
        $response = $this->get(route('customer.login', ['from' => 'carrinho']));

        $response->assertOk();
        $response->assertSee(route('customer.register', ['from' => 'carrinho']), false);
        $response->assertSee(route('customer.password.request', ['from' => 'carrinho']), false);
    }

    public function test_visiting_the_login_page_without_a_from_query_string_does_not_chain_it_into_the_links(): void
    {
        $response = $this->get(route('customer.login'));

        $response->assertOk();
        $response->assertDontSee('from=carrinho', false);
    }
}
