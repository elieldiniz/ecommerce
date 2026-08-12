<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'payment_gateway_id' => fn () => PaymentGateway::inRandomOrder()->first()?->id ?? PaymentGateway::factory()->create()->id,
            'payment_method_id' => fn () => PaymentMethod::inRandomOrder()->first()?->id ?? PaymentMethod::factory()->create()->id,
            'status_id' => fn () => PaymentStatus::inRandomOrder()->first()?->id ?? PaymentStatus::factory()->create()->id,
            'gateway_transaction_id' => fake()->unique()->uuid(),
            'gateway_status_code' => null,
            'gross_amount' => fake()->randomFloat(2, 100, 400),
            'gateway_fee' => null,
            'net_amount' => null,
            'pix_id' => fake()->uuid(),
            'end_to_end_id' => null,
            'qr_code_payload' => null,
            'boleto_digitable_line' => null,
            'receipt_url' => null,
            'installments' => null,
            'card_brand' => null,
            'card_last_digits' => null,
            'authorization_nsu' => null,
            'expires_at' => now()->addMinutes(30),
            'paid_at' => null,
            'expected_settlement_date' => null,
        ];
    }
}
