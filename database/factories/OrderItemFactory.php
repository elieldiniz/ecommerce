<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 100, 400);

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'sku_snapshot' => strtoupper(fake()->bothify('SKU-####??')),
            'name_snapshot' => 'Certificado Digital '.fake()->randomElement(['e-CPF A1', 'e-CPF A3', 'e-CNPJ A1', 'e-CNPJ A3']),
            'list_price_snapshot' => $price,
            'unit_price' => $price,
            'quantity' => 1,
            'total' => $price,
        ];
    }
}
