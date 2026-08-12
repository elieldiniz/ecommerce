<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemGfsis>
 */
class OrderItemGfsisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'status_id' => null,
            'gfsis_order_id' => fake()->unique()->numberBetween(100000, 999999),
            'gfsis_code' => null,
            'status_synced_at' => null,
            'appointment_id' => null,
            'appointment_date' => null,
            'appointment_time' => null,
            'certificate_expires_at' => null,
            'sent_at' => null,
            'attempts' => 0,
            'last_error' => null,
            'request_payload' => null,
            'response_payload' => null,
        ];
    }
}
