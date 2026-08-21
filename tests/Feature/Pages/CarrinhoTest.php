<?php

namespace Tests\Feature\Pages;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CarrinhoTest extends TestCase
{
    use RefreshDatabase;

    public function test_continuing_without_a_logged_in_customer_redirects_to_login_carrying_the_cart_intent(): void
    {
        Livewire::test('pages::carrinho')
            ->call('continuar')
            ->assertRedirect(route('customer.login', ['from' => 'carrinho']));
    }

    public function test_continuing_with_a_logged_in_customer_redirects_straight_to_checkout(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer');

        Livewire::test('pages::carrinho')
            ->call('continuar')
            ->assertRedirect(route('checkout'));
    }
}
