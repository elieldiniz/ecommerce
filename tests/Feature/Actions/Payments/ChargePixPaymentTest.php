<?php

namespace Tests\Feature\Actions\Payments;

use App\Actions\Payments\ChargePixPayment;
use App\Exceptions\Payments\PaymentTotalMismatchException;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChargePixPaymentTest extends TestCase
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

        PaymentGateway::factory()->create(['slug' => 'safe2pay']);
        PaymentStatus::factory()->create(['slug' => 'pending', 'weight' => 10]);
        Setting::factory()->create(['key' => 'pix_expiration_seconds', 'value' => '900', 'group' => 'pagamento']);
    }

    private function createOrder(string $unitPrice = '100.00'): Order
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->for($customer)->create(['is_primary' => true]);
        $paymentMethod = PaymentMethod::factory()->create(['slug' => 'pix', 'discount_percentage' => 0]);
        $order = Order::factory()->for($customer)->for($paymentMethod)->create([
            'subtotal' => $unitPrice,
            'coupon_discount' => 0,
            'payment_method_discount' => 0,
            'total' => $unitPrice,
        ]);
        OrderItem::factory()->for($order)->create(['unit_price' => $unitPrice, 'quantity' => 1]);

        return $order->fresh(['items', 'customer', 'paymentMethod']);
    }

    public function test_a_200_response_persists_the_five_fields_with_status_pending(): void
    {
        $order = $this->createOrder();
        Http::fake(['*' => Http::response([
            'ResponseDetail' => [
                'IdTransaction' => 138667690,
                'Key' => '00020126borges-copia-e-cola',
                'QrCode' => 'https://images.safe2pay.com.br/pix/exemplo.png',
            ],
            'HasError' => false,
        ], 200)]);

        $payment = (new ChargePixPayment)->execute($order, '100.00');

        $this->assertSame(1, Payment::query()->count());
        $this->assertSame('138667690', $payment->gateway_transaction_id);
        $this->assertNull($payment->pix_id);
        $this->assertSame('00020126borges-copia-e-cola', $payment->qr_code_payload);
        $this->assertSame('https://images.safe2pay.com.br/pix/exemplo.png', $payment->qr_code_image_url);
        $this->assertNotNull($payment->expires_at);
        $this->assertSame('pending', $payment->status->slug);
    }

    public function test_no_call_ever_uses_the_static_pix_endpoint(): void
    {
        $order = $this->createOrder();
        Http::fake(['*' => Http::response([
            'ResponseDetail' => ['IdTransaction' => 1, 'Key' => 'qr'],
            'HasError' => false,
        ], 200)]);

        (new ChargePixPayment)->execute($order, '100.00');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'staticPix'));
        Http::assertSent(fn ($request) => $request->url() === 'https://payment.safe2pay.com.br/v2/payment'
            && $request['PaymentMethod'] === '6');
    }

    public function test_changing_the_pix_expiration_setting_changes_the_expiration_sent_without_deploy(): void
    {
        $order = $this->createOrder();
        Setting::query()->where('key', 'pix_expiration_seconds')->update(['value' => '1800']);
        Http::fake(['*' => Http::response([
            'ResponseDetail' => ['IdTransaction' => 1, 'Key' => 'qr'],
            'HasError' => false,
        ], 200)]);

        (new ChargePixPayment)->execute($order, '100.00');

        Http::assertSent(fn ($request) => $request['PaymentObject']['Expiration'] === 1800);
    }

    public function test_a_divergent_front_total_blocks_before_any_http_call_and_creates_no_payment(): void
    {
        $order = $this->createOrder();
        Http::fake();

        try {
            (new ChargePixPayment)->execute($order, '99.99');
            $this->fail('Esperava-se PaymentTotalMismatchException.');
        } catch (PaymentTotalMismatchException) {
            // esperado
        }

        Http::assertNothingSent();
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_two_charges_for_the_same_order_create_two_rows_with_different_gateway_transaction_ids(): void
    {
        $order = $this->createOrder();
        Http::fake(function () {
            static $calls = 0;
            $calls++;

            return Http::response([
                'ResponseDetail' => ['IdTransaction' => 1000 + $calls, 'Key' => 'qr-'.$calls],
                'HasError' => false,
            ], 200);
        });

        $first = (new ChargePixPayment)->execute($order, '100.00');
        $second = (new ChargePixPayment)->execute($order, '100.00');

        $this->assertSame(2, Payment::query()->where('order_id', $order->id)->count());
        $this->assertNotSame($first->gateway_transaction_id, $second->gateway_transaction_id);
    }
}
