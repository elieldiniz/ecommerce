<?php

namespace Database\Seeders;

use App\Models\HolderType;
use Illuminate\Database\Seeder;

class HolderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Pessoa Física', 'slug' => 'pf'],
            ['name' => 'Pessoa Jurídica', 'slug' => 'pj'],
        ];

        foreach ($rows as $row) {
            HolderType::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
