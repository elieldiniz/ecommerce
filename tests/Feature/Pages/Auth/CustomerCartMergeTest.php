<?php

namespace Tests\Feature\Pages\Auth;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ProductVariant;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCartMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_login_merges_the_guest_cart_into_a_brand_new_customer_without_a_prior_cart(): void
    {
        $customer = Customer::factory()->create(['password' => 'senha12345']);
        $variant = ProductVariant::factory()->create();

        $guestCart = Cart::factory()->create(['session_id' => 'guest-session-1']);
        CartItem::factory()->create(['cart_id' => $guestCart->id, 'product_variant_id' => $variant->id, 'quantity' => 2]);

        $this->withSession(['cart_session_id' => 'guest-session-1']);

        Livewire::test('pages::auth.customer.login')
            ->set('email', $customer->email)
            ->set('password', 'senha12345')
            ->call('login')
            ->assertOk();

        $customerCart = Cart::query()->where('customer_id', $customer->id)->with('items')->firstOrFail();
        $this->assertCount(1, $customerCart->items);
        $this->assertSame($variant->id, $customerCart->items->first()->product_variant_id);
        $this->assertSame(2, $customerCart->items->first()->quantity);

        $this->assertModelMissing($guestCart);
    }

    public function test_login_merges_the_guest_cart_into_an_existing_customer_cart(): void
    {
        $customer = Customer::factory()->create(['password' => 'senha12345']);
        $existingVariant = ProductVariant::factory()->create();
        $guestVariant = ProductVariant::factory()->create();

        $customerCart = Cart::factory()->forCustomer($customer)->create();
        CartItem::factory()->create(['cart_id' => $customerCart->id, 'product_variant_id' => $existingVariant->id, 'quantity' => 1]);

        $guestCart = Cart::factory()->create(['session_id' => 'guest-session-2']);
        CartItem::factory()->create(['cart_id' => $guestCart->id, 'product_variant_id' => $guestVariant->id, 'quantity' => 1]);

        $this->withSession(['cart_session_id' => 'guest-session-2']);

        Livewire::test('pages::auth.customer.login')
            ->set('email', $customer->email)
            ->set('password', 'senha12345')
            ->call('login')
            ->assertOk();

        $customerCart->refresh()->load('items');
        $this->assertCount(2, $customerCart->items);
        $this->assertModelMissing($guestCart);
    }

    public function test_register_merges_the_guest_cart_into_the_new_customer(): void
    {
        $variant = ProductVariant::factory()->create();

        $guestCart = Cart::factory()->create(['session_id' => 'guest-session-3']);
        CartItem::factory()->create(['cart_id' => $guestCart->id, 'product_variant_id' => $variant->id, 'quantity' => 3]);

        $this->withSession(['cart_session_id' => 'guest-session-3']);

        Livewire::test('pages::auth.customer.register')
            ->set('name', 'Cliente Teste')
            ->set('email', 'cliente.teste@example.com')
            ->set('password', 'senha12345')
            ->set('password_confirmation', 'senha12345')
            ->call('register')
            ->assertOk();

        $customer = Customer::query()->where('email', 'cliente.teste@example.com')->firstOrFail();
        $customerCart = Cart::query()->where('customer_id', $customer->id)->with('items')->firstOrFail();

        $this->assertCount(1, $customerCart->items);
        $this->assertSame($variant->id, $customerCart->items->first()->product_variant_id);
        $this->assertSame(3, $customerCart->items->first()->quantity);

        $this->assertModelMissing($guestCart);
    }
}
