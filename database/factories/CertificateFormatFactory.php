<?php

namespace Database\Factories;

use App\Models\CertificateFormat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CertificateFormat>
 */
class CertificateFormatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['A1', 'A3']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'requires_hardware' => $name === 'A3',
        ];
    }
}
