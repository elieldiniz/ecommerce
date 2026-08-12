<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\HolderType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'holder_type_id' => fn () => HolderType::inRandomOrder()->first()?->id ?? HolderType::factory()->create()->id,
            'legal_name' => fake()->name(),
            'document' => fake()->unique()->numerify('###########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('###########'),
            'password_hash' => Hash::make('password'),
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'marketing_opt_in' => fake()->boolean(),
        ];
    }
}
