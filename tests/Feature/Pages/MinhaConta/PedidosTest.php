<?php

namespace Tests\Feature\Pages\MinhaConta;

use App\Models\CertificateFormat;
use App\Models\Customer;
use App\Models\HolderType;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidosTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(): Customer
    {
        return Customer::factory()->create([
            'role_id' => null,
        ]);
    }

    private function createOrderForCustomer(Customer $customer, array $overrides = []): Order
    {
        $holderType = HolderType::firstOrCreate(['slug' => 'pf'], ['name' => 'Pessoa Física']);
        $certificateFormat = CertificateFormat::firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $product = Product::firstOrCreate(['slug' => 'ecpf'], ['name' => 'Certificado Digital e-CPF', 'holder_type_id' => $holderType->id, 'short_description' => 'Teste', 'is_active' => true, 'position' => 1]);
        $variant = ProductVariant::firstOrCreate(['sku' => 'ECPF-A1-12'], [
            'product_id' => $product->id,
            'certificate_format_id' => $certificateFormat->id,
            'validity_months' => 12,
            'price' => 139.90,
            'is_active' => true,
            'is_default' => true,
        ]);

        return Order::factory()->create(array_merge([
            'customer_id' => $customer->id,
        ], $overrides))->fresh();
    }

    private function createOrderItem(Order $order): OrderItem
    {
        $variant = ProductVariant::where('sku', 'ECPF-A1-12')->first();

        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'sku_snapshot' => 'ECPF-A1-12',
            'name_snapshot' => 'Certificado Digital e-CPF A1',
        ]);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/minha-conta/pedidos/');

        $response->assertRedirect();
    }

    public function test_authenticated_route_renders_view(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/pedidos/');

        $response->assertOk();
    }

    public function test_renders_order_cards_with_real_data(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrderForCustomer($customer, [
            'status_id' => OrderStatus::firstOrCreate(['slug' => 'paid'], ['name' => 'Pago'])->id,
            'fulfillment_status_id' => OrderFulfillmentStatus::firstOrCreate(['slug' => 'awaiting_data'], ['name' => 'Aguardando dados'])->id,
            'paid_at' => now(),
        ]);
        $item = $this->createOrderItem($order);

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/pedidos/');

        $response->assertOk();
        $response->assertSee('Certificado Digital e-CPF A1');
        $response->assertSee('Aguardando dados do titular');
        $response->assertSee('Preencher agora');
        $response->assertDontSee('Maria Aparecida Souza');
        $response->assertDontSee('João Pedro Almeida');
    }

    public function test_emitido_card_shows_correct_fields(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrderForCustomer($customer, [
            'status_id' => OrderStatus::firstOrCreate(['slug' => 'paid'], ['name' => 'Pago'])->id,
            'fulfillment_status_id' => OrderFulfillmentStatus::firstOrCreate(['slug' => 'sent_to_gfsis'], ['name' => 'Enviado ao GFSIS'])->id,
            'paid_at' => now()->subDay(),
        ]);
        $item = $this->createOrderItem($order);
        $gfsis = OrderItemGfsis::factory()->emitido()->create(['order_item_id' => $item->id]);
        $issuance = IssuanceData::factory()->filled()->create([
            'order_item_id' => $item->id,
            'holder_name' => 'Maria Teste',
        ]);

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/pedidos/');

        $response->assertOk();
        $response->assertSee('Maria Teste');
        $response->assertSee('Validade até');
        $response->assertSee('Ver nota fiscal');
        $response->assertSee('Baixar certificado');
        $response->assertSee('Renovar');
    }

    public function test_agendado_card_shows_videoconference(): void
    {
        $customer = $this->createCustomer();
        $order = $this->createOrderForCustomer($customer, [
            'status_id' => OrderStatus::firstOrCreate(['slug' => 'paid'], ['name' => 'Pago'])->id,
            'fulfillment_status_id' => OrderFulfillmentStatus::firstOrCreate(['slug' => 'sent_to_gfsis'], ['name' => 'Enviado ao GFSIS'])->id,
            'paid_at' => now()->subDays(3),
        ]);
        $item = $this->createOrderItem($order);
        OrderItemGfsis::factory()->agendado()->create(['order_item_id' => $item->id]);

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/pedidos/');

        $response->assertOk();
        $response->assertSee('Videoconferência');
        $response->assertSee('Ver o que levar');
        $response->assertSee('Reagendar');
    }

    public function test_renders_conta_layout_with_sidebar(): void
    {
        $customer = $this->createCustomer();

        $response = $this->actingAs($customer, 'customer')->get('/minha-conta/pedidos/');

        $response->assertOk();
        $response->assertSee('Meus pedidos');
        $response->assertSee('Meus dados');
        $this->assertStringNotContainsString('data-step=', $response->getContent());
    }
}
