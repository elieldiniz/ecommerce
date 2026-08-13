<?php

namespace Database\Factories;

use App\Models\CertificateFormat;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'certificate_format_id' => fn () => CertificateFormat::inRandomOrder()->value('id') ?? CertificateFormat::factory()->create()->id,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'validity_months' => fake()->randomElement([12, 24, 36]),
            'price' => fake()->randomFloat(2, 100, 400),
            'promotional_price' => null,
            'promotion_starts_at' => null,
            'promotion_ends_at' => null,
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
