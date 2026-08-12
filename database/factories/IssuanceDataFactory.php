<?php

namespace Database\Factories;

use App\Models\HolderType;
use App\Models\IssuanceData;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IssuanceData>
 */
class IssuanceDataFactory extends Factory
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
            'holder_type_id' => fn () => HolderType::inRandomOrder()->first()?->id ?? HolderType::factory()->create()->id,
            'holder_name' => fake()->name(),
            'document' => fake()->numerify('###########'),
            'birth_date' => fake()->date(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('###########'),
            'postal_code' => fake()->numerify('########'),
            'street' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => null,
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR']),
            'ibge_code' => fake()->numerify('#######'),
            'responsible_name' => null,
            'responsible_document' => null,
            'responsible_birth_date' => null,
            'responsible_email' => null,
            'responsible_phone' => null,
            'access_token' => Str::random(40),
            'access_token_expires_at' => now()->addDays(7),
            'filled_at' => null,
        ];
    }
}
