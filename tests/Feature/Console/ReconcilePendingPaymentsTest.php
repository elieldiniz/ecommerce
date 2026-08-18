<?php

namespace Tests\Feature\Console;

use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Models\Setting;
use Database\Seeders\OrderStatusSeeder;
use Database\Seeders\PaymentStatusSeeder;
use Database\Seeders\QueueJobStatusSeeder;
use Database\Seeders\RefundReasonSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReconcilePendingPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.safe2pay.base_url' => 'https://payment.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.api_key_production' => 'production-key',
            'services.safe2pay.is_sandbox' => true,
        ]);

        $this->seed(PaymentStatusSeeder::class);
        $this->seed(OrderStatusSeeder::class);
        $this->seed(QueueJobStatusSeeder::class);
        $this->seed(RefundReasonSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    private function queryResponse(int $transactionStatusId): array
    {
        return [
            'TransactionStatus' => ['Id' => $transactionStatusId, 'Code' => (string) $transactionStatusId],
        ];
    }

    public function test_a_pix_payment_past_its_expiration_receives_one_query_call_and_is_marked_expired_when_not_authorized(): void
    {
        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();

        $payment = Payment::factory()->create([
            'status_id' => $pending->id,
            'gateway_transaction_id' => 'PIX-EXPIRED-1',
            'expires_at' => now()->subMinute(),
        ]);

        Http::fake(['*' => Http::response($this->queryResponse(1), 200)]);

        Artisan::call('payments:reconcile');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://payment.safe2pay.com.br/v2/payment/PIX-EXPIRED-1');

        $expired = PaymentStatus::query()->where('slug', 'expired')->firstOrFail();
        $this->assertSame($expired->id, $payment->fresh()->status_id);
    }

    public function test_a_pix_payment_not_yet_expired_is_never_queried(): void
    {
        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();

        $payment = Payment::factory()->create([
            'status_id' => $pending->id,
            'gateway_transaction_id' => 'PIX-NOT-EXPIRED',
            'expires_at' => now()->addMinutes(10),
        ]);

        Http::fake();

        Artisan::call('payments:reconcile');

        Http::assertNothingSent();
        $this->assertSame($pending->id, $payment->fresh()->status_id);
    }

    public function test_a_pix_payment_confirmed_authorized_by_the_query_is_not_forced_to_expired(): void
    {
        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();

        $payment = Payment::factory()->create([
            'status_id' => $pending->id,
            'gateway_transaction_id' => 'PIX-AUTHORIZED',
            'expires_at' => now()->subMinute(),
        ]);

        Http::fake(['*' => Http::response($this->queryResponse(3), 200)]);

        Artisan::call('payments:reconcile');

        $authorized = PaymentStatus::query()->where('slug', 'authorized')->firstOrFail();
        $this->assertSame($authorized->id, $payment->fresh()->status_id);
    }

    public function test_a_card_payment_without_expires_at_older_than_the_threshold_is_queried(): void
    {
        Setting::factory()->create([
            'key' => 'reconciliation_pending_threshold_minutes',
            'value' => '60',
            'group' => 'pagamento',
        ]);

        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();

        $payment = $this->travelTo(now()->subMinutes(90), fn () => Payment::factory()->create([
            'status_id' => $pending->id,
            'gateway_transaction_id' => 'CARD-OLD-1',
            'expires_at' => null,
        ]));

        Http::fake(['*' => Http::response($this->queryResponse(1), 200)]);

        Artisan::call('payments:reconcile');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://payment.safe2pay.com.br/v2/payment/CARD-OLD-1');

        $this->assertSame($pending->id, $payment->fresh()->status_id);
    }

    public function test_a_card_payment_created_more_recently_than_the_threshold_is_never_queried(): void
    {
        Setting::factory()->create([
            'key' => 'reconciliation_pending_threshold_minutes',
            'value' => '60',
            'group' => 'pagamento',
        ]);

        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();

        Payment::factory()->create([
            'status_id' => $pending->id,
            'gateway_transaction_id' => 'CARD-RECENT',
            'expires_at' => null,
        ]);

        Http::fake();

        Artisan::call('payments:reconcile');

        Http::assertNothingSent();
    }

    public function test_changing_the_threshold_without_deploy_changes_which_payments_are_included_in_the_next_run(): void
    {
        $setting = Setting::factory()->create([
            'key' => 'reconciliation_pending_threshold_minutes',
            'value' => '60',
            'group' => 'pagamento',
        ]);

        $pending = PaymentStatus::query()->where('slug', 'pending')->firstOrFail();

        $payment = $this->travelTo(now()->subMinutes(40), fn () => Payment::factory()->create([
            'status_id' => $pending->id,
            'gateway_transaction_id' => 'CARD-40-MIN-OLD',
            'expires_at' => null,
        ]));

        Http::fake();

        Artisan::call('payments:reconcile');

        Http::assertNothingSent();

        $setting->update(['value' => '30']);

        Http::fake(['*' => Http::response($this->queryResponse(1), 200)]);

        Artisan::call('payments:reconcile');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://payment.safe2pay.com.br/v2/payment/CARD-40-MIN-OLD');

        $this->assertSame($pending->id, $payment->fresh()->status_id);
    }
}
