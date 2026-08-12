<?php

namespace Database\Factories;

use App\Models\IntegrationQueueJob;
use App\Models\QueueJobStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrationQueueJob>
 */
class IntegrationQueueJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status_id' => fn () => QueueJobStatus::inRandomOrder()->first()?->id ?? QueueJobStatus::factory()->create()->id,
            'job' => fake()->randomElement(['send_to_gfsis', 'send_conversion', 'send_email', 'resync_status']),
            'reference_type' => 'order_item',
            'reference_id' => fake()->numberBetween(1, 1000),
            'payload' => null,
            'attempts' => 0,
            'run_at' => now(),
            'error' => null,
        ];
    }
}
