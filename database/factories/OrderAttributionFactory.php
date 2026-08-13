<?php

namespace Database\Factories;

use App\Models\DeviceType;
use App\Models\Order;
use App\Models\OrderAttribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderAttribution>
 */
class OrderAttributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'device_type_id' => fn () => DeviceType::inRandomOrder()->value('id') ?? DeviceType::factory()->create()->id,
            'gclid' => null,
            'utm_source' => fake()->randomElement(['google', 'meta', null]),
            'utm_medium' => fake()->randomElement(['cpc', 'organic', null]),
            'utm_campaign' => null,
            'utm_term' => null,
            'utm_content' => null,
            'landing_page' => fake()->url(),
            'referrer' => null,
            'first_touch_at' => now()->subHours(2),
            'sessions_before_purchase' => 1,
        ];
    }
}
