<?php

namespace Database\Factories;

use App\Models\HolderType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Certificado Digital '.fake()->randomElement(['e-CPF', 'e-CNPJ']);
        $slug = Str::slug($name).'-'.fake()->unique()->numberBetween(1, 1000000);

        return [
            'slug' => $slug,
            'name' => $name,
            'holder_type_id' => fn () => HolderType::inRandomOrder()->first()?->id ?? HolderType::factory()->create()->id,
            'short_description' => fake()->sentence(),
            'is_active' => true,
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
