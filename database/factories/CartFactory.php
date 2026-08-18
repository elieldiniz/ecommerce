<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'session_id' => fake()->unique()->uuid(),
            'coupon_id' => null,
        ];
    }

    /**
     * Cart for an authenticated customer.
     */
    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer->id,
            'session_id' => null,
        ]);
    }
}
