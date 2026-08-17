<?php

namespace Tests\Unit;

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_exactly_the_two_payment_settings(): void
    {
        $this->seed(SettingSeeder::class);

        $this->assertSame(2, Setting::query()->where('group', 'pagamento')->count());
    }

    public function test_pix_expiration_seconds_setting_has_a_valid_numeric_value(): void
    {
        $this->seed(SettingSeeder::class);

        $setting = Setting::query()->where('key', 'pix_expiration_seconds')->first();

        $this->assertNotNull($setting);
        $this->assertSame('pagamento', $setting->group);
        $this->assertIsNumeric($setting->value);
        $this->assertGreaterThan(0, (int) $setting->value);
    }

    public function test_reconciliation_pending_threshold_minutes_setting_has_a_valid_numeric_value(): void
    {
        $this->seed(SettingSeeder::class);

        $setting = Setting::query()->where('key', 'reconciliation_pending_threshold_minutes')->first();

        $this->assertNotNull($setting);
        $this->assertSame('pagamento', $setting->group);
        $this->assertIsNumeric($setting->value);
        $this->assertGreaterThan(0, (int) $setting->value);
    }

    public function test_running_the_seeder_twice_does_not_duplicate_rows(): void
    {
        $this->seed(SettingSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->assertSame(2, Setting::query()->where('group', 'pagamento')->count());
    }
}
