<?php

namespace Database\Seeders;

use App\Models\AdsConversionStatus;
use Illuminate\Database\Seeder;

class AdsConversionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Pendente', 'slug' => 'pending'],
            ['name' => 'Enviado', 'slug' => 'sent'],
            ['name' => 'Falha', 'slug' => 'failed'],
        ];

        foreach ($rows as $row) {
            AdsConversionStatus::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
