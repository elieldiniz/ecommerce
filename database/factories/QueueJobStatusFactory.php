<?php

namespace Database\Factories;

use App\Models\QueueJobStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QueueJobStatus>
 */
class QueueJobStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Pendente', 'Processando', 'Concluído', 'Falha']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
