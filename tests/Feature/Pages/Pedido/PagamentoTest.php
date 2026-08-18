<?php

namespace Tests\Feature\Pages\Pedido;

use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

class PagamentoTest extends TestCase
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
        PaymentStatus::factory()->create(['slug' => 'expired', 'weight' => 30]);
    }

    private function createOrder(string $paymentMethodSlug, string $total = '213.75'): Order
    {
        $paymentMethod = PaymentMethod::factory()->create(['slug' => $paymentMethodSlug, 'discount_percentage' => 0]);

        $order = Order::factory()->create([
            'status_id' => OrderStatus::factory()->create(['slug' => 'awaiting_payment'])->id,
            'fulfillment_status_id' => OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data'])->id,
            'payment_method_id' => $paymentMethod->id,
            'subtotal' => $total,
            'coupon_discount' => '0.00',
            'payment_method_discount' => '0.00',
            'total' => $total,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'unit_price' => $total,
            'quantity' => 1,
        ]);

        return $order;
    }

    public function test_pix_pending_payment_shows_the_real_qr_code_payload(): void
    {
        $order = $this->createOrder('pix');
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => PaymentStatus::query()->where('slug', 'pending')->value('id'),
            'qr_code_payload' => '00020126580014br.gov.bcb.pix0136real-qr-code-payload5204000053039865802BR5913DIGITAL LOCK',
        ]);

        Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->assertOk()
            ->assertSee($payment->qr_code_payload, false)
            ->assertSee('Pedido #'.$order->number);
    }

    public function test_boleto_payment_shows_the_real_digitable_line_and_receipt_url(): void
    {
        $order = $this->createOrder('boleto');
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => PaymentStatus::query()->where('slug', 'pending')->value('id'),
            'boleto_digitable_line' => '34191.79001 01043.510047 91020.150008 1 87770000021375',
            'receipt_url' => 'https://payment.safe2pay.com.br/boletos/real-receipt-url.pdf',
        ]);

        Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->assertOk()
            ->assertSee($payment->boleto_digitable_line, false)
            ->assertSee($payment->receipt_url, false);
    }

    public function test_expired_pix_payment_never_shows_its_own_qr_code_payload(): void
    {
        $order = $this->createOrder('pix');
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => PaymentStatus::query()->where('slug', 'expired')->value('id'),
            'qr_code_payload' => '00020126580014br.gov.bcb.pix0136expired-qr-code-payload5204000053039865802BR5913DIGITAL LOCK',
        ]);

        Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->assertOk()
            ->assertDontSee($payment->qr_code_payload, false)
            ->assertSee('Esse pagamento expirou');
    }

    public function test_retrying_an_expired_pix_payment_creates_a_new_payment_with_a_different_pix_id(): void
    {
        $order = $this->createOrder('pix');
        $expiredPayment = Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => PaymentStatus::query()->where('slug', 'expired')->value('id'),
            'pix_id' => 'TXID-EXPIRED',
            'qr_code_payload' => 'expired-qr-code-payload',
        ]);

        Http::fake(['*' => Http::response([
            'IdTransaction' => 999,
            'TXID' => 'TXID-NEW',
            'PaymentObject' => ['QrCode' => 'new-qr-code-payload'],
        ], 200)]);

        Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->call('tentarNovamente')
            ->assertHasNoErrors()
            ->assertSee('new-qr-code-payload', false)
            ->assertDontSee('expired-qr-code-payload', false);

        $this->assertSame(2, Payment::query()->where('order_id', $order->id)->count());
        $this->assertNotSame($expiredPayment->pix_id, Payment::query()->latest('id')->first()->pix_id);
    }

    public function test_page_reflects_status_changes_via_poll_without_client_action(): void
    {
        $order = $this->createOrder('pix');
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => PaymentStatus::query()->where('slug', 'pending')->value('id'),
            'qr_code_payload' => 'still-pending-qr-code',
        ]);

        $component = Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->assertSee('Aguardando confirmação');

        $expiredStatus = PaymentStatus::query()->where('slug', 'expired')->value('id');
        $payment->update(['status_id' => $expiredStatus]);

        $component->call('$refresh')
            ->assertSee('Esse pagamento expirou')
            ->assertDontSee($payment->qr_code_payload, false);
    }

    public function test_pix_amount_is_displayed_using_the_order_total(): void
    {
        $order = $this->createOrder('pix', '213.75');
        Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => PaymentStatus::query()->where('slug', 'pending')->value('id'),
        ]);

        Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->assertSee(Number::currency('213.75', in: 'BRL', locale: 'pt_BR'));
    }

    public function test_paid_order_redirects_to_emissao_route_with_the_real_access_token(): void
    {
        $order = $this->createOrder('pix');
        $order->update(['status_id' => OrderStatus::factory()->create(['slug' => 'paid'])->id]);

        $orderItem = OrderItem::query()->where('order_id', $order->id)->firstOrFail();
        $issuanceData = IssuanceData::factory()->create([
            'order_item_id' => $orderItem->id,
            'access_token_expires_at' => now()->addDays(30),
        ]);

        $authorized = PaymentStatus::query()->where('slug', 'authorized')->first()
            ?? PaymentStatus::factory()->create(['slug' => 'authorized', 'weight' => 60]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => $authorized->id,
        ]);

        Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->assertRedirect(route('pedido.emissao', ['id' => $order->id, 'token' => $issuanceData->access_token]));
    }

    public function test_pending_pix_payment_never_redirects_to_emissao(): void
    {
        $order = $this->createOrder('pix');
        Payment::factory()->create([
            'order_id' => $order->id,
            'status_id' => PaymentStatus::query()->where('slug', 'pending')->value('id'),
        ]);

        Livewire::test('pages::pedido.pagamento', ['id' => $order->id])
            ->assertOk()
            ->assertNoRedirect();
    }
}
