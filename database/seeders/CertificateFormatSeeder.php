<?php

namespace Database\Seeders;

use App\Models\CertificateFormat;
use Illuminate\Database\Seeder;

class CertificateFormatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'A1', 'slug' => 'a1', 'requires_hardware' => false],
            ['name' => 'A3', 'slug' => 'a3', 'requires_hardware' => true],
        ];

        foreach ($rows as $row) {
            CertificateFormat::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
