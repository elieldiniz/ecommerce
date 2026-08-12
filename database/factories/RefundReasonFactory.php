<?php

namespace Database\Factories;

use App\Models\RefundReason;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RefundReason>
 */
class RefundReasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Arrependimento', 'Garantia', 'Duplicidade', 'Chargeback', 'Outro']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
