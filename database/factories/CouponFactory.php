<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CUPOM##??')),
            'type_id' => fn () => CouponType::inRandomOrder()->first()?->id ?? CouponType::factory()->create()->id,
            'restricted_variant_id' => null,
            'value' => fake()->randomFloat(2, 5, 50),
            'usage_limit' => null,
            'uses_count' => 0,
            'per_customer_limit' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
