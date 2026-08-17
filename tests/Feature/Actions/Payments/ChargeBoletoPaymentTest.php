<?php

namespace Tests\Feature\Actions\Payments;

use App\Actions\Payments\ChargeBoletoPayment;
use App\Exceptions\Payments\PaymentTotalMismatchException;
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

class ChargeBoletoPaymentTest extends TestCase
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
    }

    private function createOrder(string $unitPrice = '100.00'): Order
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->for($customer)->create(['is_primary' => true]);
        $paymentMethod = PaymentMethod::factory()->create(['slug' => 'boleto', 'discount_percentage' => 0]);
        $order = Order::factory()->for($customer)->for($paymentMethod)->create([
            'subtotal' => $unitPrice,
            'coupon_discount' => 0,
            'payment_method_discount' => 0,
            'total' => $unitPrice,
        ]);
        OrderItem::factory()->for($order)->create(['unit_price' => $unitPrice, 'quantity' => 1]);

        return $order->fresh(['items', 'customer', 'paymentMethod']);
    }

    public function test_a_simulated_response_persists_the_digitable_line_and_the_receipt_url(): void
    {
        $order = $this->createOrder();
        Http::fake(['*' => Http::response([
            'IdTransaction' => 138667690,
            'PaymentObject' => [
                'DigitableLine' => '23793.38128 60000.000003 00000.000158 1 87540000038900',
                'Url' => 'https://safe2pay.com.br/boleto/138667690',
            ],
        ], 200)]);

        $payment = (new ChargeBoletoPayment)->execute($order, '100.00');

        $this->assertSame(1, Payment::query()->count());
        $this->assertNotEmpty($payment->boleto_digitable_line);
        $this->assertNotEmpty($payment->receipt_url);
        $this->assertSame('pending', $payment->status->slug);
    }

    public function test_a_divergent_front_total_blocks_before_any_http_call(): void
    {
        $order = $this->createOrder();
        Http::fake();

        try {
            (new ChargeBoletoPayment)->execute($order, '99.99');
            $this->fail('Esperava-se PaymentTotalMismatchException.');
        } catch (PaymentTotalMismatchException) {
            // esperado
        }

        Http::assertNothingSent();
        $this->assertSame(0, Payment::query()->count());
    }
}
