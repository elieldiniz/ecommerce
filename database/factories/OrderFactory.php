<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 100, 400);

        return [
            'number' => strtoupper(fake()->unique()->bothify('PED-######')),
            'customer_id' => Customer::factory(),
            'status_id' => fn () => OrderStatus::inRandomOrder()->value('id') ?? OrderStatus::factory()->create()->id,
            'fulfillment_status_id' => fn () => OrderFulfillmentStatus::inRandomOrder()->value('id') ?? OrderFulfillmentStatus::factory()->create()->id,
            'payment_method_id' => fn () => PaymentMethod::inRandomOrder()->value('id') ?? PaymentMethod::factory()->create()->id,
            'coupon_id' => null,
            'subtotal' => $subtotal,
            'coupon_discount' => 0,
            'payment_method_discount' => 0,
            'total' => $subtotal,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'paid_at' => null,
            'cancelled_at' => null,
            'internal_notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status_id' => OrderStatus::where('slug', 'paid')->value('id') ?? OrderStatus::factory()->create(['slug' => 'paid'])->id,
            'paid_at' => Carbon::now(),
        ]);
    }

    public function awaitingData(): static
    {
        return $this->state(fn () => [
            'fulfillment_status_id' => OrderFulfillmentStatus::where('slug', 'awaiting_data')->value('id') ?? OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data'])->id,
        ]);
    }
}
