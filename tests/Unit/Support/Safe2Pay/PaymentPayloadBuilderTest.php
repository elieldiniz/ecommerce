<?php

namespace Tests\Unit\Support\Safe2Pay;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Support\Safe2Pay\PaymentPayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_maps_every_order_item_into_a_product_with_the_four_expected_fields(): void
    {
        $order = $this->createOrder('pix');
        $variantOne = ProductVariant::factory()->create();
        $variantTwo = ProductVariant::factory()->create();

        OrderItem::factory()->for($order)->create([
            'product_variant_id' => $variantOne->id,
            'sku_snapshot' => 'SKU-1',
            'name_snapshot' => 'Certificado A',
            'unit_price' => '100.00',
            'quantity' => 2,
        ]);
        OrderItem::factory()->for($order)->create([
            'product_variant_id' => $variantTwo->id,
            'sku_snapshot' => 'SKU-2',
            'name_snapshot' => 'Certificado B',
            'unit_price' => '50.00',
            'quantity' => 1,
        ]);

        $payload = (new PaymentPayloadBuilder)->base($order->fresh(['items', 'customer', 'paymentMethod']));

        $this->assertCount(2, $payload['Products']);
        $this->assertSame([
            'Code' => 'SKU-1',
            'Description' => 'Certificado A',
            'UnitPrice' => '100.00',
            'Quantity' => 2,
        ], $payload['Products'][0]);
        $this->assertSame([
            'Code' => 'SKU-2',
            'Description' => 'Certificado B',
            'UnitPrice' => '50.00',
            'Quantity' => 1,
        ], $payload['Products'][1]);
    }

    public function test_changing_the_variant_price_after_order_creation_does_not_change_the_unit_price_in_the_payload(): void
    {
        $order = $this->createOrder('boleto');
        $variant = ProductVariant::factory()->create(['price' => '199.90']);

        OrderItem::factory()->for($order)->create([
            'product_variant_id' => $variant->id,
            'unit_price' => '199.90',
        ]);

        $variant->update(['price' => '9.99']);

        $payload = (new PaymentPayloadBuilder)->base($order->fresh(['items', 'customer', 'paymentMethod']));

        $this->assertSame('199.90', $payload['Products'][0]['UnitPrice']);
    }

    public function test_payment_method_is_derived_from_the_order_payment_method_slug(): void
    {
        $order = $this->createOrder('cartao');
        OrderItem::factory()->for($order)->create();

        $payload = (new PaymentPayloadBuilder)->base($order->fresh(['items', 'customer', 'paymentMethod']));

        $this->assertSame('2', $payload['PaymentMethod']);
    }

    public function test_base_builds_customer_and_address_from_customer_and_customer_addresses(): void
    {
        $customer = Customer::factory()->create([
            'legal_name' => 'Fulano de Tal',
            'document' => '12345678900',
            'phone' => '11999998888',
            'email' => 'fulano@example.com',
        ]);
        CustomerAddress::factory()->for($customer)->create([
            'is_primary' => true,
            'postal_code' => '01310100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
        $paymentMethod = PaymentMethod::factory()->create(['slug' => 'pix']);
        $order = Order::factory()->for($customer)->for($paymentMethod)->create();
        OrderItem::factory()->for($order)->create();

        $payload = (new PaymentPayloadBuilder)->base($order->fresh(['items', 'customer', 'paymentMethod']));

        $this->assertSame([
            'Name' => 'Fulano de Tal',
            'Identity' => '12345678900',
            'Phone' => '11999998888',
            'Email' => 'fulano@example.com',
            'Address' => [
                'ZipCode' => '01310100',
                'Street' => 'Av. Paulista',
                'Number' => '1000',
                'District' => 'Bela Vista',
                'CityName' => 'São Paulo',
                'StateInitials' => 'SP',
                'CountryName' => 'Brasil',
            ],
        ], $payload['Customer']);
    }

    public function test_base_returns_the_common_envelope_fields(): void
    {
        config(['services.safe2pay.is_sandbox' => true]);
        $order = $this->createOrder('pix');
        OrderItem::factory()->for($order)->create();

        $payload = (new PaymentPayloadBuilder)->base($order->fresh(['items', 'customer', 'paymentMethod']));

        $this->assertTrue($payload['IsSandbox']);
        $this->assertSame('Digital Lock', $payload['Application']);
        $this->assertStringContainsString('webhooks/safe2pay', $payload['CallbackUrl']);
        $this->assertSame($order->number, $payload['Reference']);
    }

    public function test_payment_payload_builder_never_hardcodes_the_safe2pay_payment_method_codes(): void
    {
        $source = file_get_contents(app_path('Support/Safe2Pay/PaymentPayloadBuilder.php'));

        $this->assertDoesNotMatchRegularExpression("/'[126]'/", $source);
    }

    private function createOrder(string $paymentMethodSlug): Order
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->for($customer)->create(['is_primary' => true]);
        $paymentMethod = PaymentMethod::factory()->create(['slug' => $paymentMethodSlug]);

        return Order::factory()->for($customer)->for($paymentMethod)->create();
    }
}
