<?php

namespace Database\Factories;

use App\Models\DeviceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeviceType>
 */
class DeviceTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Desktop', 'Mobile', 'Tablet']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
