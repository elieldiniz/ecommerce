<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Pix', 'Cartão de Crédito', 'Boleto']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'discount_percentage' => fake()->randomElement([0, 5, 10]),
            'max_installments' => fake()->randomElement([1, 6, 12]),
            'is_active' => true,
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
