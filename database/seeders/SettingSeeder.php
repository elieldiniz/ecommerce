<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            ['key' => 'pix_expiration_seconds', 'value' => '900', 'group' => 'pagamento'],
            ['key' => 'reconciliation_pending_threshold_minutes', 'value' => '60', 'group' => 'pagamento'],
            ['key' => 'gfsis_stuck_threshold_hours', 'value' => '48', 'group' => 'gfsis'],
            ['key' => 'gfsis_ponto_atendimento', 'value' => '1', 'group' => 'gfsis'],
            ['key' => 'gfsis_tipo_validacao', 'value' => '1', 'group' => 'gfsis'],
        ];

        foreach ($rows as $row) {
            Setting::query()->updateOrCreate(['key' => $row['key']], $row);
        }
    }
}
