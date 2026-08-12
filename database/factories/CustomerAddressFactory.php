<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'postal_code' => fake()->numerify('########'),
            'street' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => null,
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR']),
            'ibge_code' => fake()->numerify('#######'),
            'is_primary' => true,
        ];
    }
}
