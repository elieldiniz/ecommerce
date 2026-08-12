<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponUse>
 */
class CouponUseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'order_id' => Order::factory(),
            'customer_id' => Customer::factory(),
            'discount_applied' => fake()->randomFloat(2, 5, 50),
        ];
    }
}
