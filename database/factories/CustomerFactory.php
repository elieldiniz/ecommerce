<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\HolderType;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'holder_type_id' => fn () => HolderType::inRandomOrder()->value('id') ?? HolderType::factory()->create()->id,
            'role_id' => fn () => Role::where('slug', 'customer')->value('id'),
            'legal_name' => fake()->name(),
            'document' => fake()->unique()->numerify('###########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('###########'),
            'password' => 'password',
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'marketing_opt_in' => fake()->boolean(),
        ];
    }
}
