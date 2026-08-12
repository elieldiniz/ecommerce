<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Carrinho', 'slug' => 'cart'],
            ['name' => 'Aguardando pagamento', 'slug' => 'awaiting_payment'],
            ['name' => 'Pago', 'slug' => 'paid'],
            ['name' => 'Cancelado', 'slug' => 'cancelled'],
            ['name' => 'Reembolsado', 'slug' => 'refunded'],
            ['name' => 'Expirado', 'slug' => 'expired'],
        ];

        foreach ($rows as $row) {
            OrderStatus::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
