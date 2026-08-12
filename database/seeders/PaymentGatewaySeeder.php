<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentGateway::query()->updateOrCreate(
            ['slug' => 'safe2pay'],
            ['name' => 'Safe2Pay', 'slug' => 'safe2pay', 'is_active' => true]
        );
    }
}
