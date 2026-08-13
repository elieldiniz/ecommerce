<?php

namespace Database\Factories;

use App\Models\AdsConversion;
use App\Models\AdsConversionStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdsConversion>
 */
class AdsConversionFactory extends Factory
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
            'status_id' => fn () => AdsConversionStatus::inRandomOrder()->value('id') ?? AdsConversionStatus::factory()->create()->id,
            'transaction_id' => fake()->unique()->uuid(),
            'gclid' => null,
            'amount' => fake()->randomFloat(2, 100, 400),
            'currency' => 'BRL',
            'attempts' => 0,
            'sent_at' => null,
            'response' => null,
        ];
    }
}
