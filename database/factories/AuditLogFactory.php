<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => 'alterar_status_pedido',
            'entity' => 'orders',
            'entity_id' => fake()->numberBetween(1, 1000),
            'data_before' => null,
            'data_after' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
