<?php

namespace Database\Seeders;

use App\Models\GfsisStatus;
use Illuminate\Database\Seeder;

class GfsisStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Enviado ao GFSIS', 'slug' => 'enviado_gfsis'],
            ['name' => 'Aprovado', 'slug' => 'aprovado'],
            ['name' => 'Emitido', 'slug' => 'emitido'],
            ['name' => 'Falha no envio', 'slug' => 'falha_envio'],
            ['name' => 'Cancelado', 'slug' => 'cancelado'],
        ];

        foreach ($rows as $row) {
            GfsisStatus::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
