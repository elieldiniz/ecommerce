<?php

namespace Database\Seeders;

use App\Models\PaymentStatus;
use Illuminate\Database\Seeder;

class PaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * weight é a ordem lógica usada para impedir regressão de status fora de ordem
     * (database-schema.md → payment_statuses.weight). Os valores concretos são
     * detalhe de implementação, desde que cresçam no caminho feliz.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Pendente', 'slug' => 'pending', 'weight' => 10],
            ['name' => 'Em análise', 'slug' => 'under_review', 'weight' => 20],
            ['name' => 'Negado', 'slug' => 'denied', 'weight' => 30],
            ['name' => 'Expirado', 'slug' => 'expired', 'weight' => 30],
            ['name' => 'Autorizado', 'slug' => 'authorized', 'weight' => 40],
            ['name' => 'Estornado', 'slug' => 'reversed', 'weight' => 50],
        ];

        foreach ($rows as $row) {
            PaymentStatus::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
