<?php

namespace Tests\Feature\CrossCutting;

use App\Actions\Payments\ChargeCardPayment;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Support\Safe2Pay\Safe2PayClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Testes cross-cutting de RNF-01 (nenhum dado completo de cartão persistido),
 * RNF-02 (X-API-KEY de produção nunca em asset publicado) e RNF-03 (IsSandbox
 * sempre via config, nunca literal fixo) — T26.
 */
class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const FORBIDDEN_CARD_COLUMNS = [
        'card_number', 'cvv', 'security_code', 'card_cvv', 'card_expiration', 'expiration_date',
    ];

    // RNF-01

    public function test_payments_table_never_has_columns_for_full_card_data(): void
    {
        $columns = Schema::getColumnListing('payments');

        foreach (self::FORBIDDEN_CARD_COLUMNS as $forbidden) {
            $this->assertNotContains($forbidden, $columns, "Coluna proibida '{$forbidden}' encontrada em payments (RNF-01).");
        }
    }

    public function test_payment_fillable_never_includes_full_card_data(): void
    {
        $fillable = (new Payment)->getFillable();

        foreach (self::FORBIDDEN_CARD_COLUMNS as $forbidden) {
            $this->assertNotContains($forbidden, $fillable, "'{$forbidden}' não deve ser fillable em Payment (RNF-01).");
        }
    }

    public function test_a_successful_card_charge_response_persists_only_brand_last_digits_and_nsu(): void
    {
        config([
            'services.safe2pay.base_url' => 'https://payment.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.api_key_production' => 'production-key',
            'services.safe2pay.is_sandbox' => true,
        ]);

        PaymentGateway::factory()->create(['slug' => 'safe2pay']);
        PaymentStatus::factory()->create(['slug' => 'authorized', 'weight' => 40]);

        $customer = Customer::factory()->create();
        CustomerAddress::factory()->for($customer)->create(['is_primary' => true]);
        $paymentMethod = PaymentMethod::factory()->create(['slug' => 'cartao', 'discount_percentage' => 0, 'max_installments' => 12]);
        $order = Order::factory()->for($customer)->for($paymentMethod)->create([
            'subtotal' => '100.00', 'coupon_discount' => 0, 'payment_method_discount' => 0, 'total' => '100.00',
        ]);
        OrderItem::factory()->for($order)->create(['unit_price' => '100.00', 'quantity' => 1]);
        $order = $order->fresh(['items', 'customer', 'paymentMethod']);

        Http::fake(['*' => Http::response([
            'IdTransaction' => 138667690,
            'TransactionStatus' => ['Id' => 3, 'Code' => '3', 'Name' => 'Autorizado'],
            'PaymentObject' => [
                'InstallmentQuantity' => 1,
                'Brand' => 'Visa',
                'LastDigits' => '1111',
                'Nsu' => 'NSU-000123',
                'CardNumber' => '4111111111111111',
                'SecurityCode' => '123',
            ],
        ], 200)]);

        $payment = (new ChargeCardPayment)->execute($order, '100.00', 'token-cofre-de-chaves', 1, 'visitor-abc');

        $attributes = $payment->refresh()->getAttributes();

        foreach (self::FORBIDDEN_CARD_COLUMNS as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $attributes, "'{$forbidden}' não deve ser persistido em payments (RNF-01).");
        }

        $this->assertSame('Visa', $payment->card_brand);
        $this->assertSame('1111', $payment->card_last_digits);
        $this->assertSame('NSU-000123', $payment->authorization_nsu);
    }

    // RNF-02

    public function test_no_frontend_source_asset_references_the_safe2pay_production_api_key(): void
    {
        $files = [
            ...File::allFiles(resource_path('js')),
            ...File::allFiles(resource_path('css')),
        ];

        foreach ($files as $file) {
            $content = File::get($file->getPathname());

            $this->assertStringNotContainsString('SAFE2PAY_API_KEY', $content, "O arquivo {$file->getPathname()} não deve referenciar SAFE2PAY_API_KEY (RNF-02).");
            $this->assertStringNotContainsString('api_key_production', $content, "O arquivo {$file->getPathname()} não deve referenciar api_key_production (RNF-02).");
        }
    }

    public function test_no_published_build_asset_contains_the_safe2pay_production_api_key_string(): void
    {
        $buildPath = public_path('build');

        if (! File::isDirectory($buildPath)) {
            $this->markTestSkipped('public/build não existe — assets não foram publicados neste ambiente.');
        }

        foreach (File::allFiles($buildPath) as $file) {
            $content = File::get($file->getPathname());

            $this->assertStringNotContainsString('SAFE2PAY_API_KEY', $content, "O asset publicado {$file->getPathname()} não deve conter SAFE2PAY_API_KEY (RNF-02).");
            $this->assertStringNotContainsString('api_key_production', $content, "O asset publicado {$file->getPathname()} não deve conter api_key_production (RNF-02).");
            $this->assertStringNotContainsString('X-API-KEY', $content, "O asset publicado {$file->getPathname()} não deve conter X-API-KEY (RNF-02).");
        }
    }

    // RNF-03

    public function test_safe2pay_client_never_hardcodes_is_sandbox_outside_the_configuration_point(): void
    {
        $source = File::get(app_path('Support/Safe2Pay/Safe2PayClient.php'));

        $sourceWithoutConfigurationPoint = str_replace(
            "'IsSandbox' => \$this->isSandbox(),",
            '',
            $source
        );

        $this->assertStringNotContainsString("'IsSandbox' => true", $sourceWithoutConfigurationPoint, 'Safe2PayClient::charge() não pode ter IsSandbox fixo em true (RNF-03).');
        $this->assertStringNotContainsString("'IsSandbox' => false", $sourceWithoutConfigurationPoint, 'Safe2PayClient::charge() não pode ter IsSandbox fixo em false (RNF-03).');
        $this->assertStringContainsString("config('services.safe2pay.is_sandbox')", $source, 'IsSandbox deve ser lido de config() (RNF-03).');
    }

    public function test_charge_sends_is_sandbox_reflecting_config_not_a_literal(): void
    {
        config([
            'services.safe2pay.base_url' => 'https://payment.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.api_key_production' => 'production-key',
        ]);

        Http::fake(['*' => Http::response(['IdTransaction' => 1], 200)]);

        config(['services.safe2pay.is_sandbox' => true]);
        (new Safe2PayClient)->charge(['PaymentMethod' => '6']);
        Http::assertSent(fn ($request) => $request['IsSandbox'] === true);

        config(['services.safe2pay.is_sandbox' => false]);
        (new Safe2PayClient)->charge(['PaymentMethod' => '6']);
        Http::assertSent(fn ($request) => $request['IsSandbox'] === false);
    }
}
