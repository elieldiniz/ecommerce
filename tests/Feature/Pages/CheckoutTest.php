<?php

namespace Tests\Feature\Pages;

use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\CouponUse;
use App\Models\Customer;
use App\Models\CustomerAddress;
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
            'services.safe2pay.installment_base_url' => 'https://api.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.api_key_production' => 'production-key',
            'services.safe2pay.is_sandbox' => true,
        ]);

        // Sem registrar nenhum 'Http::fake([...])' aqui (ele teria prioridade sobre qualquer fake
        // que um teste declare depois — Http::fake() sempre mantém a PRIMEIRA regra registrada
        // para um padrão de URL, não a mais recente). preventStrayRequests() sozinho já intercepta
        // qualquer chamada não fakeada (lança StrayRequestException, capturada pelo catch(\Throwable)
        // de FetchInstallmentValues) — suficiente para os ~15 testes que não têm nada a ver com
        // parcelamento não tentarem rede real, já que "Cartão de crédito" está sempre na lista de
        // formas de pagamento (RF-01/RF-03 rodam em toda renderização do checkout).
        Http::preventStrayRequests();

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
            'street' => 'Av. Paulista',
            'number' => '1000',
            'complement' => 'Sala 2',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];
    }

    public function test_switching_payment_method_updates_the_three_totals_without_reloading_the_document(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['api.safe2pay.com.br/*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '200.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
            ]],
        ], 200)]);

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

    public function test_the_installment_dropdown_reflects_exactly_the_rows_returned_by_safe2pay(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['api.safe2pay.com.br/*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '200.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
                ['Installments' => 2, 'InstallmentValue' => '100.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
                ['Installments' => 3, 'InstallmentValue' => '68.66', 'TotalValue' => '205.98', 'AppliedTax' => 2.99],
            ]],
        ], 200)]);

        $html = Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->call('selecionarFormaPagamento', 'cartao')
            ->html();

        preg_match('/wire:model\.live="installments".*?<\/select>/s', $html, $matches);
        $this->assertNotEmpty($matches, 'Select de parcelas não encontrado no HTML.');
        $this->assertSame(3, substr_count($matches[0], '<option'));
        $this->assertStringContainsString('1x '.Number::currency('200.00', in: 'BRL', locale: 'pt_BR').' sem juros ('.Number::currency('200.00', in: 'BRL', locale: 'pt_BR').')', $html);
        $this->assertStringContainsString('2x '.Number::currency('100.00', in: 'BRL', locale: 'pt_BR').' sem juros ('.Number::currency('200.00', in: 'BRL', locale: 'pt_BR').')', $html);
        $this->assertStringContainsString('3x '.Number::currency('68.66', in: 'BRL', locale: 'pt_BR').' ('.Number::currency('205.98', in: 'BRL', locale: 'pt_BR').')', $html);
    }

    public function test_the_cartao_badge_shows_the_maximum_installment_count_with_no_interest(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['api.safe2pay.com.br/*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '200.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
                ['Installments' => 4, 'InstallmentValue' => '50.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
                ['Installments' => 6, 'InstallmentValue' => '35.30', 'TotalValue' => '211.80', 'AppliedTax' => 5.9],
            ]],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->assertSee('Até 4x sem juros');
    }

    public function test_the_cartao_badge_is_hidden_when_no_installment_option_is_interest_free(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['api.safe2pay.com.br/*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '204.00', 'TotalValue' => '204.00', 'AppliedTax' => 2],
            ]],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->assertDontSee('sem juros');
    }

    public function test_a_failed_installment_value_query_disables_the_cartao_card_and_blocks_selection(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['api.safe2pay.com.br/*' => Http::response(['HasError' => true], 200)]);

        $html = Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->call('selecionarFormaPagamento', 'cartao')
            ->assertSet('paymentMethodSlug', 'pix')
            ->html();

        preg_match('/<button[^>]*data-payment-method="cartao"[^>]*>/s', $html, $matches);
        $this->assertNotEmpty($matches, 'Botão do card "Cartão de crédito" não encontrado.');
        $this->assertStringContainsString('disabled', $matches[0]);
    }

    public function test_a_recovered_installment_value_query_re_enables_the_cartao_selection(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');

        // Fake mutável em vez de Http::sequence(): a badge "Cartão de crédito" já consulta CT-01
        // na renderização inicial da página (antes de qualquer ->call() explícito), então o número
        // exato de chamadas até o primeiro ->call() não é previsível — o que importa é que TODAS as
        // chamadas antes de $succeed=true falhem, e TODAS depois tenham sucesso.
        $succeed = false;
        Http::fake(['api.safe2pay.com.br/*' => function () use (&$succeed) {
            if (! $succeed) {
                return Http::response(['HasError' => true], 200);
            }

            return Http::response([
                'HasError' => false,
                'ResponseDetail' => ['Installments' => [
                    ['Installments' => 1, 'InstallmentValue' => '200.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
                ]],
            ], 200);
        }]);

        $component = Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->call('selecionarFormaPagamento', 'cartao')
            ->assertSet('paymentMethodSlug', 'pix');

        $succeed = true;

        $component->call('selecionarFormaPagamento', 'cartao')
            ->assertSet('paymentMethodSlug', 'cartao');
    }

    public function test_two_consecutive_cartao_selections_with_the_same_subtotal_call_safe2pay_only_once(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['api.safe2pay.com.br/*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '200.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
            ]],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->call('selecionarFormaPagamento', 'cartao')
            ->call('selecionarFormaPagamento', 'pix')
            ->call('selecionarFormaPagamento', 'cartao');

        Http::assertSentCount(1);
    }

    public function test_applying_a_coupon_that_changes_the_subtotal_triggers_a_new_installment_value_call(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        $coupon = $this->createCoupon(['code' => 'DESC20']);
        Http::fake(['api.safe2pay.com.br/*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '200.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
            ]],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->call('selecionarFormaPagamento', 'cartao')
            ->set('couponCode', $coupon->code)
            ->call('aplicarCupom')
            ->call('selecionarFormaPagamento', 'pix')
            ->call('selecionarFormaPagamento', 'cartao');

        Http::assertSentCount(2);
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

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->call('selecionarFormaPagamento', 'pix')
            ->set('confirmedTotal', '999.99')
            ->call('finalizarCompra')
            ->assertHasErrors('geral')
            ->assertNoRedirect();

        // A badge de "Cartão de crédito" consulta CT-01 em toda renderização do checkout (RF-01/
        // RF-03), então a garantia real deste teste é que nenhuma chamada de COBRANÇA (POST) é
        // feita — não que zero chamadas HTTP ocorram no total.
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_switching_payment_method_after_a_pending_charge_creates_a_new_payment_without_cancelling_the_previous_one(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        // Registrado antes do fake '*' abaixo (Http::fake mantém a primeira regra registrada por
        // padrão de URL) para que a troca pra "cartao" adiante realmente consiga suceder.
        Http::fake(['api.safe2pay.com.br/*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => ['Installments' => [
                ['Installments' => 1, 'InstallmentValue' => '200.00', 'TotalValue' => '200.00', 'AppliedTax' => 0],
            ]],
        ], 200)]);
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

    public function test_missing_street_number_neighborhood_city_or_state_blocks_submission(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');

        foreach (['street', 'number', 'neighborhood', 'city', 'state'] as $field) {
            Livewire::test('pages::checkout', ['variant' => $variant->id])
                ->set($this->validCustomerFields())
                ->set($field, '')
                ->call('selecionarFormaPagamento', 'pix')
                ->call('finalizarCompra')
                ->assertHasErrors($field)
                ->assertNoRedirect();
        }

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, CustomerAddress::query()->count());
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_a_postal_code_with_fewer_than_eight_digits_blocks_submission(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->set('postalCode', '123')
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasErrors('postalCode')
            ->assertNoRedirect();

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, CustomerAddress::query()->count());
    }

    public function test_an_invalid_state_blocks_submission_while_a_valid_state_does_not(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->set('state', 'XX')
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasErrors('state')
            ->assertNoRedirect();

        $this->assertSame(0, Order::query()->count());

        Http::fake(['*' => Http::response([
            'IdTransaction' => 1,
            'TXID' => 'TXID-1',
            'PaymentObject' => ['QrCode' => 'qr'],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->set('state', 'SP')
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasNoErrors('state');
    }

    public function test_a_valid_submission_persists_exactly_one_primary_customer_address_before_charging(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['*' => Http::response([
            'IdTransaction' => 1,
            'TXID' => 'TXID-1',
            'PaymentObject' => ['QrCode' => 'qr'],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasNoErrors();

        $customer = Customer::query()->sole();
        $address = CustomerAddress::query()->where('customer_id', $customer->id)->where('is_primary', true)->sole();

        $this->assertSame(1, CustomerAddress::query()->where('customer_id', $customer->id)->where('is_primary', true)->count());
        $this->assertMatchesRegularExpression('/^\d{8}$/', $address->postal_code);
        $this->assertSame('Av. Paulista', $address->street);
        $this->assertSame('1000', $address->number);
        $this->assertSame('Sala 2', $address->complement);
        $this->assertSame('Bela Vista', $address->neighborhood);
        $this->assertSame('São Paulo', $address->city);
        $this->assertSame('SP', $address->state);
    }

    public function test_resubmitting_with_a_different_address_updates_the_single_primary_row_instead_of_creating_a_second_one(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['*' => Http::response([
            'IdTransaction' => 1,
            'TXID' => 'TXID-1',
            'PaymentObject' => ['QrCode' => 'qr'],
        ], 200)]);

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasNoErrors();

        $customer = Customer::query()->sole();

        Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->set('street', 'Rua Nova Endereço')
            ->set('city', 'Campinas')
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasNoErrors();

        $this->assertSame(1, CustomerAddress::query()->where('customer_id', $customer->id)->where('is_primary', true)->count());
        $address = CustomerAddress::query()->where('customer_id', $customer->id)->where('is_primary', true)->sole();
        $this->assertSame('Rua Nova Endereço', $address->street);
        $this->assertSame('Campinas', $address->city);
    }

    public function test_has_error_true_from_safe2pay_shows_a_generic_error_and_creates_no_payment(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant('200.00');
        Http::fake(['*' => Http::response([
            'HasError' => true,
            'ErrorCode' => '023',
            'Error' => 'A rua do endereço do cliente informado é inválida.',
        ], 200)]);

        $component = Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set($this->validCustomerFields())
            ->call('selecionarFormaPagamento', 'pix')
            ->call('finalizarCompra')
            ->assertHasErrors('geral')
            ->assertNoRedirect();

        $message = $component->errors()->first('geral');
        $this->assertStringNotContainsString('HasError', $message);
        $this->assertStringNotContainsString('023', $message);
        $this->assertStringNotContainsString('inválida', $message);
        $this->assertSame(0, Payment::query()->count());
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

    public function test_checkout_layout_exposes_a_csrf_token_meta_tag(): void
    {
        $this->createPaymentMethods();
        $this->createProductVariant();

        $this->get('/checkout/')->assertSee('name="csrf-token"', false);
    }

    public function test_no_public_property_or_wire_model_exists_for_raw_card_fields(): void
    {
        $this->createPaymentMethods();
        $variant = $this->createProductVariant();

        $component = Livewire::test('pages::checkout', ['variant' => $variant->id])
            ->set('paymentMethodSlug', 'cartao');

        $publicProperties = array_map(
            fn (\ReflectionProperty $property) => $property->getName(),
            (new \ReflectionClass($component->instance()))->getProperties(\ReflectionProperty::IS_PUBLIC),
        );

        $forbiddenNames = ['cardNumber', 'cardHolder', 'cardExpiry', 'cardCvv', 'securityCode', 'expirationDate'];
        $this->assertEmpty(array_intersect($publicProperties, $forbiddenNames));

        $html = $component->html();
        foreach (['s2p-card-number', 's2p-card-holder', 's2p-card-expiry', 's2p-card-cvv'] as $id) {
            preg_match('/<input[^>]*id="'.$id.'"[^>]*>/', $html, $matches);
            $this->assertNotEmpty($matches, "Input #{$id} not found in rendered HTML.");
            $this->assertStringNotContainsString('wire:model', $matches[0]);
        }
    }
}
