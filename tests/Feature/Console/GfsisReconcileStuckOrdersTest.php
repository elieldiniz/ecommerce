<?php

namespace Tests\Feature\Console;

use App\Models\GfsisStatus;
use App\Models\OrderItemGfsis;
use App\Models\Setting;
use Database\Seeders\GfsisStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GfsisReconcileStuckOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GfsisStatusSeeder::class);

        Setting::factory()->create([
            'key' => 'gfsis_stuck_threshold_hours',
            'value' => '48',
            'group' => 'gfsis',
        ]);
    }

    private function enviadoGfsisStatusId(): int
    {
        return GfsisStatus::query()->where('slug', 'enviado_gfsis')->value('id');
    }

    public function test_an_order_item_gfsis_stuck_past_the_threshold_is_included_in_the_output_and_logged(): void
    {
        Log::spy();
        Http::fake();

        $stuck = OrderItemGfsis::factory()->create([
            'status_id' => $this->enviadoGfsisStatusId(),
            'sent_at' => now()->subHours(49),
        ]);

        Artisan::call('gfsis:reconcile-stuck');

        Log::shouldHaveReceived('warning')->once()->with('gfsis.stuck_order_item', [
            'order_item_gfsis_id' => $stuck->id,
            'gfsis_order_id' => $stuck->gfsis_order_id,
            'sent_at' => $stuck->sent_at->toDateTimeString(),
        ]);

        $this->assertStringContainsString((string) $stuck->gfsis_order_id, Artisan::output());

        Http::assertNothingSent();
    }

    public function test_an_order_item_gfsis_within_the_threshold_is_not_included(): void
    {
        Log::spy();
        Http::fake();

        $recent = OrderItemGfsis::factory()->create([
            'status_id' => $this->enviadoGfsisStatusId(),
            'sent_at' => now()->subHours(10),
        ]);

        Artisan::call('gfsis:reconcile-stuck');

        Log::shouldNotHaveReceived('warning');
        $this->assertStringNotContainsString((string) $recent->gfsis_order_id, Artisan::output());

        Http::assertNothingSent();
    }

    public function test_an_order_item_gfsis_with_a_different_status_is_not_included_even_when_old(): void
    {
        Log::spy();
        Http::fake();

        $aprovado = GfsisStatus::query()->where('slug', 'aprovado')->value('id');

        $notStuck = OrderItemGfsis::factory()->create([
            'status_id' => $aprovado,
            'sent_at' => now()->subHours(100),
        ]);

        Artisan::call('gfsis:reconcile-stuck');

        Log::shouldNotHaveReceived('warning');
        $this->assertStringNotContainsString((string) $notStuck->gfsis_order_id, Artisan::output());

        Http::assertNothingSent();
    }

    public function test_no_http_call_is_ever_made(): void
    {
        Http::fake();

        OrderItemGfsis::factory()->create([
            'status_id' => $this->enviadoGfsisStatusId(),
            'sent_at' => now()->subHours(200),
        ]);

        Artisan::call('gfsis:reconcile-stuck');

        Http::assertNothingSent();
    }

    public function test_changing_the_threshold_without_deploy_changes_the_result_of_the_next_run(): void
    {
        Log::spy();
        Http::fake();

        $setting = Setting::query()->where('key', 'gfsis_stuck_threshold_hours')->firstOrFail();

        $item = OrderItemGfsis::factory()->create([
            'status_id' => $this->enviadoGfsisStatusId(),
            'sent_at' => now()->subHours(30),
        ]);

        Artisan::call('gfsis:reconcile-stuck');
        Log::shouldNotHaveReceived('warning');

        $setting->update(['value' => '24']);

        Artisan::call('gfsis:reconcile-stuck');
        Log::shouldHaveReceived('warning')->once()->with('gfsis.stuck_order_item', [
            'order_item_gfsis_id' => $item->id,
            'gfsis_order_id' => $item->gfsis_order_id,
            'sent_at' => $item->sent_at->toDateTimeString(),
        ]);
    }
}
