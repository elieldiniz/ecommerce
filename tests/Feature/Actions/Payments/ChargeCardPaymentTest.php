<?php

namespace Tests\Feature\Actions\Payments;

use App\Actions\Payments\ChargeCardPayment;
use App\Exceptions\Payments\InstallmentLimitExceededException;
use App\Exceptions\Payments\MissingVisitorIdException;
use App\Exceptions\Payments\PaymentTotalMismatchException;
use App\Exceptions\Payments\Safe2PayChargeFailedException;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChargeCardPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.safe2pay.base_url' => 'https://payment.safe2pay.com.br',
            'services.safe2pay.installment_base_url' => 'https://api.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.api_key_production' => 'production-key',
            'services.safe2pay.is_sandbox' => true,
        ]);

        PaymentGateway::factory()->create(['slug' => 'safe2pay']);
        PaymentStatus::factory()->create(['slug' => 'pending', 'weight' => 10]);
        PaymentStatus::factory()->create(['slug' => 'under_review', 'weight' => 20]);
        PaymentStatus::factory()->create(['slug' => 'authorized', 'weight' => 40]);
    }

    private function createOrder(string $unitPrice = '100.00', int $maxInstallments = 12): Order
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->for($customer)->create(['is_primary' => true]);
        $paymentMethod = PaymentMethod::factory()->create([
            'slug' => 'cartao',
            'discount_percentage' => 0,
            'max_installments' => $maxInstallments,
        ]);
        $order = Order::factory()->for($customer)->for($paymentMethod)->create([
            'subtotal' => $unitPrice,
            'coupon_discount' => 0,
            'payment_method_discount' => 0,
            'total' => $unitPrice,
        ]);
        OrderItem::factory()->for($order)->create(['unit_price' => $unitPrice, 'quantity' => 1]);

        return $order->fresh(['items', 'customer', 'paymentMethod']);
    }

    private function fakeAuthorizedResponse(): void
    {
        Http::fake(['*' => Http::response([
            'ResponseDetail' => [
                'IdTransaction' => 138667690,
                'Status' => 3,
                'Tid' => '020006495642',
                'AuthorizationCode' => '205340',
                'CreditCard' => [
                    'CardNumber' => '402400******1111',
                    'Brand' => 1,
                    'Installments' => 3,
                ],
            ],
            'HasError' => false,
        ], 200)]);
    }

    public function test_the_payload_never_contains_card_number_security_code_or_expiration_date(): void
    {
        $order = $this->createOrder();
        $this->fakeAuthorizedResponse();

        (new ChargeCardPayment)->execute($order, '100.00', 'token-cofre-de-chaves', 3, 'visitor-abc');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return ! array_key_exists('CardNumber', $body['PaymentObject'] ?? [])
                && ! array_key_exists('SecurityCode', $body['PaymentObject'] ?? [])
                && ! array_key_exists('ExpirationDate', $body['PaymentObject'] ?? []);
        });
    }

    public function test_installments_above_the_maximum_are_rejected_without_any_http_call(): void
    {
        $order = $this->createOrder(maxInstallments: 12);
        Http::fake();

        try {
            (new ChargeCardPayment)->execute($order, '100.00', 'token', 13, 'visitor-abc');
            $this->fail('Esperava-se InstallmentLimitExceededException.');
        } catch (InstallmentLimitExceededException) {
            // esperado
        }

        Http::assertNothingSent();
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_an_empty_visitor_id_is_rejected_without_any_http_call(): void
    {
        $order = $this->createOrder();
        Http::fake();

        try {
            (new ChargeCardPayment)->execute($order, '100.00', 'token', 3, '');
            $this->fail('Esperava-se MissingVisitorIdException.');
        } catch (MissingVisitorIdException) {
            // esperado
        }

        Http::assertNothingSent();
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_every_successful_call_contains_should_use_anti_fraud_true_and_a_non_empty_visitor_id(): void
    {
        $order = $this->createOrder();
        $this->fakeAuthorizedResponse();

        (new ChargeCardPayment)->execute($order, '100.00', 'token', 3, 'visitor-abc');

        Http::assertSent(fn ($request) => ($request['ShouldUseAntiFraud'] ?? null) === true
            && ! empty($request['VisitorID'] ?? null));
    }

    public function test_a_200_response_persists_the_five_fields_and_leaves_settlement_fields_null(): void
    {
        $order = $this->createOrder();
        $this->fakeAuthorizedResponse();

        $payment = (new ChargeCardPayment)->execute($order, '100.00', 'token', 3, 'visitor-abc');

        $this->assertSame(1, Payment::query()->count());
        $this->assertSame('138667690', $payment->gateway_transaction_id);
        $this->assertSame(3, $payment->installments);
        $this->assertSame('Visa', $payment->card_brand);
        $this->assertSame('1111', $payment->card_last_digits);
        $this->assertSame('020006495642', $payment->authorization_nsu);
        $this->assertSame('authorized', $payment->status->slug);
        $this->assertNull($payment->gateway_fee);
        $this->assertNull($payment->net_amount);
        $this->assertNull($payment->expected_settlement_date);
    }

    public function test_a_divergent_front_total_blocks_without_ever_calling_the_charge_endpoint(): void
    {
        // RF-04: o guard agora consulta o parcelamento (leitura, GET) antes de comparar o
        // total — a garantia que este teste protege é que o endpoint de COBRANÇA (POST
        // /v2/payment) nunca é chamado para um total divergente, não que zero chamadas HTTP
        // ocorram (a consulta de parcelamento em si é inofensiva/sem efeito colateral).
        $order = $this->createOrder();
        Http::fake();

        try {
            (new ChargeCardPayment)->execute($order, '99.99', 'token', 3, 'visitor-abc');
            $this->fail('Esperava-se PaymentTotalMismatchException.');
        } catch (PaymentTotalMismatchException) {
            // esperado
        }

        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_an_unrecognized_brand_code_stores_the_raw_code_instead_of_failing(): void
    {
        $order = $this->createOrder();
        Http::fake(['*' => Http::response([
            'ResponseDetail' => [
                'IdTransaction' => 138667690,
                'Status' => 3,
                'Tid' => '020006495642',
                'CreditCard' => [
                    'CardNumber' => '402400******1111',
                    'Brand' => 999,
                    'Installments' => 3,
                ],
            ],
            'HasError' => false,
        ], 200)]);

        $payment = (new ChargeCardPayment)->execute($order, '100.00', 'token', 3, 'visitor-abc');

        $this->assertSame('999', $payment->card_brand);
    }

    public function test_has_error_true_in_a_200_response_throws_and_creates_no_payment(): void
    {
        $order = $this->createOrder();
        Http::fake(['*' => Http::response([
            'HasError' => true,
            'ErrorCode' => '023',
            'Error' => 'A rua do endereço do cliente informado é inválida.',
            'RequestId' => '40001559-0000-e500-b63f-84710c7967bb',
        ], 200)]);

        try {
            (new ChargeCardPayment)->execute($order, '100.00', 'token', 3, 'visitor-abc');
            $this->fail('Esperava-se Safe2PayChargeFailedException.');
        } catch (Safe2PayChargeFailedException $exception) {
            $this->assertSame('023', $exception->rawResponse()['ErrorCode']);
        }

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_when_the_selected_installment_has_applied_tax_the_guard_rejects_the_base_total(): void
    {
        $order = $this->createOrder();
        Http::fake([
            'api.safe2pay.com.br/*' => Http::response([
                'HasError' => false,
                'ResponseDetail' => ['Installments' => [
                    ['Installments' => 3, 'InstallmentValue' => '36.63', 'TotalValue' => '109.90', 'AppliedTax' => 2.99],
                ]],
            ], 200),
            'payment.safe2pay.com.br/*' => Http::response(['HasError' => true], 200),
        ]);

        try {
            (new ChargeCardPayment)->execute($order, '100.00', 'token', 3, 'visitor-abc');
            $this->fail('Esperava-se PaymentTotalMismatchException.');
        } catch (PaymentTotalMismatchException) {
            // esperado
        }

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'payment.safe2pay.com.br'));
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_when_the_selected_installment_has_applied_tax_the_guard_accepts_the_safe2pay_total_value(): void
    {
        $order = $this->createOrder();
        Http::fake([
            'api.safe2pay.com.br/*' => Http::response([
                'HasError' => false,
                'ResponseDetail' => ['Installments' => [
                    ['Installments' => 3, 'InstallmentValue' => '36.63', 'TotalValue' => '109.90', 'AppliedTax' => 2.99],
                ]],
            ], 200),
            'payment.safe2pay.com.br/*' => Http::response([
                'ResponseDetail' => [
                    'IdTransaction' => 138667690,
                    'Status' => 3,
                    'Tid' => '020006495642',
                    'CreditCard' => ['CardNumber' => '402400******1111', 'Brand' => 1, 'Installments' => 3],
                ],
                'HasError' => false,
            ], 200),
        ]);

        $payment = (new ChargeCardPayment)->execute($order, '109.90', 'token', 3, 'visitor-abc');

        $this->assertSame('100.00', $payment->gross_amount);
    }

    public function test_when_the_installment_query_fails_the_guard_falls_back_to_the_base_total(): void
    {
        $order = $this->createOrder();
        Http::fake([
            'api.safe2pay.com.br/*' => Http::response([], 500),
            'payment.safe2pay.com.br/*' => Http::response([
                'ResponseDetail' => [
                    'IdTransaction' => 138667690,
                    'Status' => 3,
                    'Tid' => '020006495642',
                    'CreditCard' => ['CardNumber' => '402400******1111', 'Brand' => 1, 'Installments' => 3],
                ],
                'HasError' => false,
            ], 200),
        ]);

        $payment = (new ChargeCardPayment)->execute($order, '100.00', 'token', 3, 'visitor-abc');

        $this->assertSame('100.00', $payment->gross_amount);
    }
}
