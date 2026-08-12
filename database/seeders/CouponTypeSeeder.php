<?php

namespace Database\Seeders;

use App\Models\CouponType;
use Illuminate\Database\Seeder;

class CouponTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Percentual', 'slug' => 'percentage'],
            ['name' => 'Valor fixo', 'slug' => 'fixed_amount'],
        ];

        foreach ($rows as $row) {
            CouponType::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
