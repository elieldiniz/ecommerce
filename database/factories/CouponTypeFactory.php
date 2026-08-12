<?php

namespace Database\Factories;

use App\Models\CouponType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CouponType>
 */
class CouponTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Percentual', 'Valor fixo']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
