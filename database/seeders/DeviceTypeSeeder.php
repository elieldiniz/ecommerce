<?php

namespace Database\Seeders;

use App\Models\DeviceType;
use Illuminate\Database\Seeder;

class DeviceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Desktop', 'slug' => 'desktop'],
            ['name' => 'Mobile', 'slug' => 'mobile'],
            ['name' => 'Tablet', 'slug' => 'tablet'],
        ];

        foreach ($rows as $row) {
            DeviceType::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
