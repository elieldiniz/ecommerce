<?php

namespace Database\Seeders;

use App\Models\OrderFulfillmentStatus;
use Illuminate\Database\Seeder;

class OrderFulfillmentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Aguardando dados', 'slug' => 'awaiting_data'],
            ['name' => 'Dados completos', 'slug' => 'data_complete'],
            ['name' => 'Enviado ao GFSIS', 'slug' => 'sent_to_gfsis'],
            ['name' => 'Falha no envio', 'slug' => 'send_failed'],
        ];

        foreach ($rows as $row) {
            OrderFulfillmentStatus::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
