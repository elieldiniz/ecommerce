<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            HolderTypeSeeder::class,
            CertificateFormatSeeder::class,
            PaymentMethodSeeder::class,
            PaymentGatewaySeeder::class,
            OrderStatusSeeder::class,
            OrderFulfillmentStatusSeeder::class,
            PaymentStatusSeeder::class,
            RefundReasonSeeder::class,
            SettingSeeder::class,
            CouponTypeSeeder::class,
            DeviceTypeSeeder::class,
            AdsConversionStatusSeeder::class,
            QueueJobStatusSeeder::class,
            RoleSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
    }
}
