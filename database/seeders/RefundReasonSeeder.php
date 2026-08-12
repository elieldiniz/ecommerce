<?php

namespace Database\Seeders;

use App\Models\RefundReason;
use Illuminate\Database\Seeder;

class RefundReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Arrependimento', 'slug' => 'withdrawal_right'],
            ['name' => 'Garantia', 'slug' => 'warranty'],
            ['name' => 'Duplicidade', 'slug' => 'duplicate'],
            ['name' => 'Chargeback', 'slug' => 'chargeback'],
            ['name' => 'Outro', 'slug' => 'other'],
        ];

        foreach ($rows as $row) {
            RefundReason::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
