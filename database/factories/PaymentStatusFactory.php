<?php

namespace Database\Factories;

use App\Models\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentStatus>
 */
class PaymentStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Pendente', 'Autorizado', 'Expirado', 'Estornado', 'Negado', 'Em análise']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'weight' => fake()->numberBetween(1, 60),
        ];
    }
}
