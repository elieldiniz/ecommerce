<?php

namespace Tests\Feature\Console;

use App\Actions\Gfsis\GenerateIssuanceAccessToken;
use App\Actions\Gfsis\ResendIssuanceAccessLink;
use App\Mail\IssuanceAccessLinkMail;
use App\Models\IntegrationQueueJob;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Database\Seeders\OrderFulfillmentStatusSeeder;
use Database\Seeders\OrderStatusSeeder;
use Database\Seeders\QueueJobStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecuperacaoEnviarReforcoEmail24hTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OrderStatusSeeder::class);
        $this->seed(OrderFulfillmentStatusSeeder::class);
        $this->seed(QueueJobStatusSeeder::class);

        Setting::factory()->create([
            'key' => 'recovery_reinforcement_email_threshold_hours',
            'value' => '24',
            'group' => 'recuperacao',
        ]);
    }

    private function makeQueuedOrder(int $paidHoursAgo): Order
    {
        $order = Order::factory()->paid()->awaitingData()->create(['paid_at' => now()->subHours($paidHoursAgo)]);

        OrderItem::factory()->create(['order_id' => $order->id]);

        $order = $order->fresh(['items', 'customer']);

        (new GenerateIssuanceAccessToken)->execute($order);

        return $order;
    }

    public function test_an_order_past_the_threshold_receives_the_reinforcement_email_with_a_regenerated_token(): void
    {
        Mail::fake();

        $order = $this->makeQueuedOrder(25);
        $tokenAntigo = IssuanceData::query()->where('order_item_id', $order->items->first()->id)->value('access_token');

        Artisan::call('recuperacao:reforco-24h');

        Mail::assertSent(IssuanceAccessLinkMail::class, fn ($mail) => $mail->hasTo($order->customer->email));

        $tokenNovo = IssuanceData::query()->where('order_item_id', $order->items->first()->id)->value('access_token');
        $this->assertNotSame($tokenAntigo, $tokenNovo);
    }

    public function test_an_order_within_the_threshold_does_not_receive_the_email(): void
    {
        Mail::fake();

        $this->makeQueuedOrder(10);

        Artisan::call('recuperacao:reforco-24h');

        Mail::assertNothingSent();
    }

    public function test_running_the_command_twice_sends_the_reinforcement_email_only_once(): void
    {
        Mail::fake();

        $this->makeQueuedOrder(25);

        Artisan::call('recuperacao:reforco-24h');
        Artisan::call('recuperacao:reforco-24h');

        Mail::assertSent(IssuanceAccessLinkMail::class, 1);
    }

    public function test_records_the_idempotency_mark_in_integration_queue_after_sending(): void
    {
        Mail::fake();

        $order = $this->makeQueuedOrder(25);

        Artisan::call('recuperacao:reforco-24h');

        $this->assertTrue(
            IntegrationQueueJob::query()
                ->where('job', 'recovery_email_24h')
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->whereHas('status', fn ($query) => $query->where('slug', 'completed'))
                ->exists()
        );
    }

    public function test_a_manual_resend_does_not_record_the_idempotency_mark_nor_block_the_automatic_reinforcement(): void
    {
        Mail::fake();

        $order = $this->makeQueuedOrder(25);

        app(ResendIssuanceAccessLink::class)->execute($order);

        $this->assertDatabaseMissing('integration_queue', [
            'job' => 'recovery_email_24h',
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        Artisan::call('recuperacao:reforco-24h');

        Mail::assertSent(IssuanceAccessLinkMail::class, 2);
    }

    public function test_changing_the_threshold_without_deploy_changes_the_result_of_the_next_run(): void
    {
        Mail::fake();

        $setting = Setting::query()->where('key', 'recovery_reinforcement_email_threshold_hours')->firstOrFail();

        $this->makeQueuedOrder(20);

        Artisan::call('recuperacao:reforco-24h');
        Mail::assertNothingSent();

        $setting->update(['value' => '12']);

        Artisan::call('recuperacao:reforco-24h');
        Mail::assertSent(IssuanceAccessLinkMail::class, 1);
    }

    public function test_a_failure_processing_one_order_does_not_stop_the_others_in_the_same_run(): void
    {
        Mail::fake();
        Log::spy();

        // Pedido órfão (sem order_item) simula dado inconsistente que nenhum self-heal resolve.
        $ordemComFalha = Order::factory()->paid()->awaitingData()->create(['paid_at' => now()->subHours(30)]);

        $ordemOk = $this->makeQueuedOrder(30);

        Artisan::call('recuperacao:reforco-24h');

        Mail::assertSent(IssuanceAccessLinkMail::class, 1);

        $this->assertDatabaseMissing('integration_queue', [
            'job' => 'recovery_email_24h',
            'reference_id' => $ordemComFalha->id,
        ]);

        $this->assertDatabaseHas('integration_queue', [
            'job' => 'recovery_email_24h',
            'reference_id' => $ordemOk->id,
        ]);

        Log::shouldHaveReceived('error')->once();
    }
}
