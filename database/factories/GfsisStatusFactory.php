<?php

namespace Database\Factories;

use App\Models\GfsisStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GfsisStatus>
 */
class GfsisStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
