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
        ];

        foreach ($rows as $row) {
            Setting::query()->updateOrCreate(['key' => $row['key']], $row);
        }
    }
}
