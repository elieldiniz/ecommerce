<?php

namespace Database\Factories;

use App\Models\GfsisEvent;
use App\Models\OrderItemGfsis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GfsisEvent>
 */
class GfsisEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gfsis_order_id' => OrderItemGfsis::factory()->create()->gfsis_order_id,
            'event_hash' => hash('sha256', Str::uuid()->toString()),
            'received_status' => fake()->word(),
            'payload' => ['status' => fake()->word()],
            'received_at' => now(),
            'processed_at' => null,
            'error' => null,
        ];
    }
}
