<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundReason;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
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
            'reason_id' => fn () => RefundReason::inRandomOrder()->value('id') ?? RefundReason::factory()->create()->id,
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 100, 400),
            'requires_revocation' => false,
            'revocation_confirmed_at' => null,
            'requested_at' => now(),
            'completed_at' => null,
        ];
    }
}
