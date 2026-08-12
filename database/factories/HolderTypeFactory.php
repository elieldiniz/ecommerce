<?php

namespace Database\Factories;

use App\Models\HolderType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HolderType>
 */
class HolderTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Pessoa Física', 'Pessoa Jurídica']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
