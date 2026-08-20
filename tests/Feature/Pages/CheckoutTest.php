<?php

namespace Tests\Feature\Pages;

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\CouponUse;
use App\Models\Customer;
use App\Models\HolderType;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

        OrderStatus::factory()->create(['slug' => 'awaiting_payment']);
        OrderFulfillmentStatus::factory()->create(['slug' => 'awaiting_data']);
        PaymentGateway::factory()->create(['slug' => 'safe2pay']);
        PaymentStatus::factory()->create(['slug' => 'pending', 'weight' => 10]);
        PaymentStatus::factory()->create(['slug' => 'under_review', 'weight' => 20]);
        PaymentStatus::factory()->create(['slug' => 'authorized', 'weight' => 40]);
        HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);
        HolderType::factory()->create(['slug' => 'pj', 'name' => 'Pessoa Jurídica']);
        Setting::factory()->create(['key' => 'pix_expiration_seconds', 'value' => '900', 'group' => 'pagamento']);
    }

    private function createProductVariant(string $price = '200.00'): ProductVariant
    {
        return ProductVariant::factory()->for(Product::factory())->create([
            'price' => $price,
            'promotional_price' => null,
        ]);
    }

    private function createPaymentMethods(int $cardMaxInstallments = 12): void
    {
        PaymentMethod::factory()->create(['slug' => 'pix', 'name' => 'Pix', 'discount_percentage' => 5, 'max_installments' => 1, 'position' => 0]);
        PaymentMethod::factory()->create(['slug' => 'cartao', 'name' => 'Cartão de crédito', 'discount_percentage' => 0, 'max_installments' => $cardMaxInstallments, 'position' => 1]);
        PaymentMethod::factory()->create(['slug' => 'boleto', 'name' => 'Boleto', 'discount_percentage' => 0, 'max_installments' => 1, 'position' => 2]);
    }

    /**
     * @return array<string, string>
     */
    private function validCustomerFields(string $document = '12345678000199'): array
    {
        return [
            'personType' => 'pj',
            'document' => $document,
            'legalName' => 'Comércio Digital Lock Ltda',
            'email' => 'contato@empresaexemplo.com.br',
            'phone' => '(11) 91234-5678',
            'postalCode' => '01311-000',
        ];
    }

    public function test_switching_payment_method_updates_the_three_totals_without_reloading_the_document(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');

        $component = Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->assertOk()
            ->assertSee(Number::currency('200.00', in: 'BRL', locale: 'pt_BR'))
            ->assertSee(Number::currency('190.00', in: 'BRL', locale: 'pt_BR'));

        $component->call('selecionarFormaPagamento', 'cartao')
            ->assertOk()
            ->assertSee(Number::currency('200.00', in: 'BRL', locale: 'pt_BR'))
            ->assertDontSee(Number::currency('190.00', in: 'BRL', locale: 'pt_BR'));

        $component->assertNoRedirect();
    }

    public function test_installment_options_never_exceed_max_installments_and_reflect_db_changes_on_next_render(): void
    {
        $this->createPaymentMethods(cardMaxInstallments: 12);
        $variant = $this->createProductVariant('200.00');

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->call('selecionarFormaPagamento', 'cartao')
            ->assertSee('12x')
            ->assertDontSee('13x');

        PaymentMethod::query()->where('slug', 'cartao')->update(['max_installments' => 3]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->call('selecionarFormaPagamento', 'cartao')
            ->assertSee('3x')
            ->assertDontSee('4x');
    }

    public function test_a_valid_submission_creates_exactly_one_order_and_one_payment(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['*' => Http::response([
            'IdTransaction' => 123456,
            'TXID' => 'TXID-ABC',
            'PaymentObject' => ['QrCode' => '00020126copia-e-cola'],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasNoErrors()
            ->assertRedirect(route('pedido.pagamento', ['id' => Order::query()->first()->id]));

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame('pending', Payment::query()->first()->status->slug);
    }

    public function test_reusing_an_email_already_tied_to_a_different_document_shows_an_error_instead_of_crashing(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Customer::factory()->create(['document' => '99988877000166', 'email' => 'contato@empresaexemplo.com.br']);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields(document: '12345678000199'))
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasErrors('email')
            ->assertNoRedirect();

        $this->assertSame(1, Customer::query()->count());
        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_divergent_front_total_blocks_payment_creation_and_shows_an_error(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake();

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->call('selecionarFormaPagamento', 'pix')
            ->set('confirmedTotal', '999.99')
            ->call('finalizarCompra')
            ->assertHasErrors('geral')
            ->assertNoRedirect();

        Http::assertNothingSent();
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_switching_payment_method_after_a_pending_charge_creates_a_new_payment_without_cancelling_the_previous_one(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['*' => Http::response([
            'IdTransaction' => 1,
            'TXID' => 'TXID-1',
            'TransactionStatus' => ['Id' => 3],
            'PaymentObject' => ['QrCode' => 'qr', 'InstallmentQuantity' => 1, 'Brand' => 'Visa', 'LastDigits' => '4242', 'Nsu' => 'NSU-1'],
        ], 200)]);

        $component = Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasNoErrors();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, Payment::query()->count());
        $firstPayment = Payment::query()->first();

        $component->call('selecionarFormaPagamento', 'cartao')
            ->set('cardToken', 'tok_abc123')
            ->set('visitorId', 'visitor-abc123')
            ->call('finalizarCompra')
            ->assertHasNoErrors();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(2, Payment::query()->count());
        $this->assertSame('pending', $firstPayment->fresh()->status->slug);
    }

    private function createCoupon(array $attributes = []): Coupon
    {
        return Coupon::factory()->create([
            'type_id' => CouponType::factory()->create(['slug' => 'fixed_amount'])->id,
            'value' => '20.00',
            ...$attributes,
        ]);
    }

    public function test_an_unknown_coupon_code_shows_a_not_found_error_and_applies_no_discount(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set('couponCode', 'NAOEXISTE')
            ->assertSee('Cupom não encontrado.')
            ->assertSet('coupon', null);
    }

    public function test_an_expired_coupon_shows_an_error_and_applies_no_discount(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        $coupon = $this->createCoupon(['code' => 'VENCIDO', 'starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set('couponCode', $coupon->code)
            ->assertSee('Este cupom expirou.')
            ->assertSet('coupon', null);
    }

    public function test_a_coupon_at_its_usage_limit_shows_an_error_and_applies_no_discount(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        $coupon = $this->createCoupon(['code' => 'ESGOTADO', 'usage_limit' => 1, 'uses_count' => 1]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set('couponCode', $coupon->code)
            ->assertSee('Este cupom atingiu o limite de usos.')
            ->assertSet('coupon', null);
    }

    public function test_a_coupon_restricted_to_a_different_variant_shows_an_error_and_applies_no_discount(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        $otherVariant = $this->createProductVariant('150.00');
        $coupon = $this->createCoupon(['code' => 'OUTROVAR', 'restricted_variant_id' => $otherVariant->id]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set('couponCode', $coupon->code)
            ->assertSee('Este cupom não é válido para o produto selecionado.')
            ->assertSet('coupon', null);
    }

    public function test_a_coupon_at_its_per_customer_limit_shows_an_error_once_the_document_is_filled(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        $customer = Customer::factory()->create(['document' => '12345678000199']);
        $coupon = $this->createCoupon(['code' => 'JAUSADO', 'per_customer_limit' => 1]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'customer_id' => $customer->id]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set('document', '12345678000199')
            ->set('couponCode', $coupon->code)
            ->assertSee('Você já utilizou este cupom o número máximo de vezes permitido.')
            ->assertSet('coupon', null);
    }

    public function test_a_valid_coupon_shows_no_error_and_is_resolved(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        $coupon = $this->createCoupon(['code' => 'VALIDO']);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set('couponCode', $coupon->code)
            ->assertDontSee('Cupom não encontrado.')
            ->assertSet('couponError', null)
            ->assertSet('coupon.id', $coupon->id);
    }

    public function test_an_invalid_coupon_at_submission_time_is_silently_dropped_and_the_order_has_no_discount(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        $coupon = $this->createCoupon(['code' => 'ESGOTADO2', 'usage_limit' => 1, 'uses_count' => 1]);
        Http::fake(['*' => Http::response([
            'IdTransaction' => 1,
            'TXID' => 'TXID-1',
            'PaymentObject' => ['QrCode' => 'qr'],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->set('couponCode', $coupon->code)
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasNoErrors();

        $order = Order::query()->sole();
        $this->assertNull($order->coupon_id);
        $this->assertSame('0.00', (string) $order->coupon_discount);
        $this->assertSame(0, CouponUse::query()->count());
    }
}
