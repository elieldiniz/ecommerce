<?php

namespace Database\Factories;

use App\Models\GfsisStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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

    public function enviadoGfsis(): static
    {
        return $this->state(fn () => [
            'status_id' => GfsisStatus::where('slug', 'enviado_gfsis')->value('id') ?? GfsisStatus::factory()->create(['slug' => 'enviado_gfsis'])->id,
            'sent_at' => Carbon::now(),
            'status_synced_at' => Carbon::now(),
        ]);
    }

    public function agendado(): static
    {
        return $this->state(fn () => [
            'appointment_date' => Carbon::today()->addDays(3),
            'appointment_time' => '14:30:00',
        ]);
    }

    public function emitido(): static
    {
        return $this->state(fn () => [
            'status_id' => GfsisStatus::where('slug', 'emitido')->value('id') ?? GfsisStatus::factory()->create(['slug' => 'emitido'])->id,
            'certificate_expires_at' => Carbon::now()->addYear(),
            'status_synced_at' => Carbon::now(),
        ]);
    }

    public function vencendo(): static
    {
        return $this->state(fn () => [
            'status_id' => GfsisStatus::where('slug', 'emitido')->value('id') ?? GfsisStatus::factory()->create(['slug' => 'emitido'])->id,
            'certificate_expires_at' => Carbon::now()->addDays(30),
            'status_synced_at' => Carbon::now(),
        ]);
    }
}
