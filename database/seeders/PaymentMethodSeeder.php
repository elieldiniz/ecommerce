<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Valores de desconto/parcelas são placeholders de exemplo do documento de
     * referência, a substituir por valores reais de negócio antes de produção
     * (ver database-schema.md → Open Questions).
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Pix', 'slug' => 'pix', 'discount_percentage' => 0, 'max_installments' => 1, 'is_active' => true, 'position' => 0],
            ['name' => 'Cartão de crédito', 'slug' => 'cartao', 'discount_percentage' => 0, 'max_installments' => 12, 'is_active' => true, 'position' => 1],
            ['name' => 'Boleto', 'slug' => 'boleto', 'discount_percentage' => 0, 'max_installments' => 1, 'is_active' => true, 'position' => 2],
        ];

        foreach ($rows as $row) {
            PaymentMethod::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
