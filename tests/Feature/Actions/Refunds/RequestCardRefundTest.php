<?php

namespace Tests\Feature\Actions\Refunds;

use App\Actions\Refunds\RequestBoletoRefund;
use App\Actions\Refunds\RequestCardRefund;
use App\Exceptions\Refunds\RefundNotAllowedException;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentStatus;
use App\Models\Refund;
use App\Models\RefundReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RequestCardRefundTest extends TestCase
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
    }

    private function authorizedPayment(): Payment
    {
        $status = PaymentStatus::query()->firstOrCreate(['slug' => 'authorized'], ['name' => 'Autorizado', 'weight' => 40]);

        return Payment::factory()->create([
            'status_id' => $status->id,
            'gateway_transaction_id' => 'TXN-CARD-1',
        ]);
    }

    public function test_a_payment_that_is_not_authorized_is_rejected_without_creating_a_refund(): void
    {
        $pending = PaymentStatus::query()->firstOrCreate(['slug' => 'pending'], ['name' => 'Pendente', 'weight' => 10]);
        $payment = Payment::factory()->create(['status_id' => $pending->id]);
        $reason = RefundReason::factory()->create();
        $user = User::factory()->create();

        Http::fake();

        try {
            (new RequestCardRefund)->execute($payment, $reason, $user, '127.0.0.1');
            $this->fail('Esperava-se RefundNotAllowedException.');
        } catch (RefundNotAllowedException) {
            // esperado
        }

        $this->assertSame(0, Refund::query()->count());
        Http::assertNothingSent();
    }

    public function test_a_valid_request_creates_the_refund_immediately_but_payment_status_only_changes_via_webhook(): void
    {
        $payment = $this->authorizedPayment();
        $reason = RefundReason::factory()->create();
        $user = User::factory()->create();

        Http::fake(['*' => Http::response(['Success' => true], 200)]);

        $refund = (new RequestCardRefund)->execute($payment, $reason, $user, '127.0.0.1');

        $this->assertSame(1, Refund::query()->count());
        $this->assertSame($payment->id, $refund->payment_id);
        $this->assertSame('authorized', $payment->fresh()->status->slug);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === 'https://payment.safe2pay.com.br/v2/payment/TXN-CARD-1/estornar');
    }

    public function test_a_failed_sync_call_creates_exactly_one_additional_audit_log_referencing_the_refund(): void
    {
        $payment = $this->authorizedPayment();
        $reason = RefundReason::factory()->create();
        $user = User::factory()->create();

        Http::fake(['*' => Http::response(['Message' => 'Erro na Safe2Pay'], 500)]);

        $refund = (new RequestCardRefund)->execute($payment, $reason, $user, '127.0.0.1');

        $this->assertSame(1, Refund::query()->count());
        $this->assertSame(2, AuditLog::query()->count());

        $failureLog = AuditLog::query()->where('action', 'card_refund_sync_call_failed')->first();
        $this->assertNotNull($failureLog);
        $this->assertSame('refund', $failureLog->entity);
        $this->assertSame($refund->id, $failureLog->entity_id);
        $this->assertSame(['Message' => 'Erro na Safe2Pay'], $failureLog->data_after);
    }

    public function test_no_request_boleto_refund_class_exists_in_the_feature_source(): void
    {
        $this->assertFileDoesNotExist(app_path('Actions/Refunds/RequestBoletoRefund.php'));
        $this->assertFalse(class_exists(RequestBoletoRefund::class));
    }
}
