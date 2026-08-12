<?php

namespace Database\Factories;

use App\Models\OrderFulfillmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderFulfillmentStatus>
 */
class OrderFulfillmentStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Aguardando dados', 'Dados completos', 'Enviado ao GFSIS', 'Falha no envio']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
