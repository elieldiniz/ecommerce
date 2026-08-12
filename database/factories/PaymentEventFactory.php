<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentEvent>
 */
class PaymentEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'gateway_transaction_id' => fake()->uuid(),
            'event_hash' => hash('sha256', Str::uuid()->toString()),
            'received_status' => fake()->word(),
            'payload' => ['status' => fake()->word()],
            'received_at' => now(),
            'processed_at' => null,
            'error' => null,
        ];
    }
}
