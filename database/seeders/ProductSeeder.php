<?php

namespace Database\Seeders;

use App\Models\HolderType;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pf = HolderType::query()->where('slug', 'pf')->first();
        $pj = HolderType::query()->where('slug', 'pj')->first();

        $rows = [
            [
                'slug' => 'e-cpf',
                'name' => 'Certificado Digital e-CPF',
                'holder_type_id' => $pf->id,
                'short_description' => 'Para pessoas físicas. Assinar documentos, declarar Imposto de Renda e acessar serviços do governo.',
                'is_active' => true,
                'position' => 1,
            ],
            [
                'slug' => 'e-cnpj',
                'name' => 'Certificado Digital e-CNPJ',
                'holder_type_id' => $pj->id,
                'short_description' => 'Para empresas. Emitir nota fiscal eletrônica, acessar o e-CAC, enviar eSocial e assinar em nome da empresa.',
                'is_active' => true,
                'position' => 2,
            ],
            [
                'slug' => 'mei',
                'name' => 'Certificado Digital para MEI',
                'holder_type_id' => $pj->id,
                'short_description' => 'Para microempreendedor individual. Emitir nota fiscal e cumprir as obrigações do CNPJ.',
                'is_active' => true,
                'position' => 3,
            ],
        ];

        foreach ($rows as $row) {
            Product::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
