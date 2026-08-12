<?php

namespace Database\Factories;

use App\Models\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderStatus>
 */
class OrderStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Carrinho', 'Aguardando pagamento', 'Pago', 'Cancelado', 'Reembolsado', 'Expirado']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
