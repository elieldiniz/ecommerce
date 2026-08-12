<?php

namespace Database\Seeders;

use App\Models\QueueJobStatus;
use Illuminate\Database\Seeder;

class QueueJobStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['name' => 'Pendente', 'slug' => 'pending'],
            ['name' => 'Processando', 'slug' => 'processing'],
            ['name' => 'Concluído', 'slug' => 'completed'],
            ['name' => 'Falha', 'slug' => 'failed'],
        ];

        foreach ($rows as $row) {
            QueueJobStatus::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
