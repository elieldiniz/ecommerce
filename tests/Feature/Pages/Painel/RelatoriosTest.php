<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\CertificateFormat;
use App\Models\Customer;
use App\Models\HolderType;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;
use Tests\TestCase;

class RelatoriosTest extends TestCase
{
    use RefreshDatabase;

    private function orderStatus(string $slug): OrderStatus
    {
        return OrderStatus::where('slug', $slug)->first()
            ?? OrderStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    private function fulfillmentStatus(string $slug): OrderFulfillmentStatus
    {
        return OrderFulfillmentStatus::where('slug', $slug)->first()
            ?? OrderFulfillmentStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    private function paidOrder(array $attributes = []): Order
    {
        return Order::factory()->create([
            'status_id' => $this->orderStatus('paid')->id,
            'fulfillment_status_id' => $this->fulfillmentStatus('data_complete')->id,
            'paid_at' => now(),
            ...$attributes,
        ]);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/relatorios/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_relatorios_view(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.relatorios')->assertOk();
    }

    public function test_block_selecao_renders_nine_report_cards_with_vendas_por_periodo_selected_by_default(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Vendas por período');
        $response->assertSee('Vendas por produto');
        $response->assertSee('Funil operacional');
        $response->assertSee('Pagos sem dados');
        $response->assertSee('Base de renovação');
        $response->assertSee('Atribuição');
        $response->assertSee('Conciliação do gateway');
        $response->assertSee('Estornos');
        $response->assertSee('Cupons');

        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, 'border-2 border-brand bg-highlight'));

        preg_match('/<button[^>]*data-report="vendas-por-periodo"[^>]*>.*?<\/button>/s', $content, $matches);
        $this->assertStringContainsString('border-2 border-brand bg-highlight', $matches[0] ?? '');
    }

    public function test_vendas_por_periodo_kpis_reflect_real_paid_orders(): void
    {
        $this->actingAs(User::factory()->create());

        $this->paidOrder(['total' => 200, 'coupon_discount' => 20, 'payment_method_discount' => 0]);
        $this->paidOrder(['total' => 300, 'coupon_discount' => 0, 'payment_method_discount' => 10]);

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Faturamento');
        $response->assertSee(Number::currency(500, in: 'BRL', locale: 'pt_BR'), false);
        $response->assertSee('Pedidos');
        $response->assertSee('2');
        $response->assertSee('Ticket médio');
        $response->assertSee(Number::currency(250, in: 'BRL', locale: 'pt_BR'), false);
        $response->assertSee('Descontos');
        $response->assertSee(Number::currency(30, in: 'BRL', locale: 'pt_BR'), false);
    }

    public function test_vendas_por_periodo_daily_table_groups_orders_by_day(): void
    {
        $this->actingAs(User::factory()->create());

        $this->paidOrder(['total' => 100, 'paid_at' => now()->subDay()]);
        $this->paidOrder(['total' => 150, 'paid_at' => now()->subDay()]);
        $this->paidOrder(['total' => 200, 'paid_at' => now()]);

        $response = $this->get('/painel/relatorios/');

        $response->assertSeeInOrder(['Dia', 'Pedidos', 'Faturamento', 'Ticket médio', 'Desconto']);
        $response->assertSee(now()->subDay()->format('d/m/Y'));
        $response->assertSee(now()->format('d/m/Y'));
        $response->assertSee(Number::currency(250, in: 'BRL', locale: 'pt_BR'), false);
        $response->assertSee(Number::currency(200, in: 'BRL', locale: 'pt_BR'), false);
    }

    public function test_vendas_por_periodo_filters_by_product(): void
    {
        $this->actingAs(User::factory()->create());

        $productA = Product::factory()->create(['name' => 'Produto A']);
        $productB = Product::factory()->create(['name' => 'Produto B']);
        $variantA = ProductVariant::factory()->create(['product_id' => $productA->id]);
        $variantB = ProductVariant::factory()->create(['product_id' => $productB->id]);

        $orderA = $this->paidOrder(['total' => 111]);
        OrderItem::factory()->create(['order_id' => $orderA->id, 'product_variant_id' => $variantA->id]);

        $orderB = $this->paidOrder(['total' => 222]);
        OrderItem::factory()->create(['order_id' => $orderB->id, 'product_variant_id' => $variantB->id]);

        Livewire::test('pages::painel.relatorios')
            ->set('produto', $productA->id)
            ->assertSee(Number::currency(111, in: 'BRL', locale: 'pt_BR'), false)
            ->assertDontSee(Number::currency(222, in: 'BRL', locale: 'pt_BR'), false);
    }

    public function test_vendas_por_periodo_filters_by_payment_method(): void
    {
        $this->actingAs(User::factory()->create());

        $pix = PaymentMethod::factory()->create(['name' => 'Pix', 'slug' => 'pix']);
        $boleto = PaymentMethod::factory()->create(['name' => 'Boleto', 'slug' => 'boleto']);

        $this->paidOrder(['total' => 333, 'payment_method_id' => $pix->id]);
        $this->paidOrder(['total' => 444, 'payment_method_id' => $boleto->id]);

        Livewire::test('pages::painel.relatorios')
            ->set('formaPagamento', $pix->id)
            ->assertSee(Number::currency(333, in: 'BRL', locale: 'pt_BR'), false)
            ->assertDontSee(Number::currency(444, in: 'BRL', locale: 'pt_BR'), false);
    }

    public function test_vendas_por_periodo_search_filters_by_customer_name(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create(['legal_name' => 'Ana Beatriz Ramos']);
        $this->paidOrder(['total' => 555, 'customer_id' => $customer->id]);
        $this->paidOrder(['total' => 666]);

        Livewire::test('pages::painel.relatorios')
            ->set('busca', 'Ana Beatriz')
            ->assertSee(Number::currency(555, in: 'BRL', locale: 'pt_BR'), false)
            ->assertDontSee(Number::currency(666, in: 'BRL', locale: 'pt_BR'), false);
    }

    public function test_vendas_por_periodo_shows_empty_state_when_no_paid_orders(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Nenhuma venda no período selecionado');
        $response->assertSee(Number::currency(0, in: 'BRL', locale: 'pt_BR'), false);
    }

    public function test_exportar_csv_streams_a_csv_response(): void
    {
        $this->actingAs(User::factory()->create());

        $this->paidOrder(['total' => 250]);

        Livewire::test('pages::painel.relatorios')
            ->call('exportarCsv')
            ->assertOk();
    }

    public function test_base_de_renovacao_shows_real_customer_and_product(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::query()->firstOrCreate(['slug' => 'pf'], ['name' => 'Pessoa Física']);
        $a1 = CertificateFormat::query()->firstOrCreate(['slug' => 'a1'], ['name' => 'A1', 'requires_hardware' => false]);
        $product = Product::factory()->create(['name' => 'Certificado Digital e-CPF', 'holder_type_id' => $pf->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'certificate_format_id' => $a1->id]);

        $customer = Customer::factory()->create([
            'legal_name' => 'Carlos Eduardo Nogueira',
            'document' => '555.666.777-88',
            'phone' => '11987654321',
        ]);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'product_variant_id' => $variant->id]);
        OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'certificate_expires_at' => now()->addDays(21),
        ]);

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Base de renovação');
        $response->assertSeeInOrder(['Titular', 'Documento', 'Produto', 'Vence em', 'Dias', 'Contato']);
        $response->assertSee('Carlos Eduardo Nogueira');
        $response->assertSee('555.666.777-88');
        $response->assertSee('Certificado Digital e-CPF A1');
        $response->assertSee(now()->addDays(21)->format('d/m/Y'));
        $response->assertSee('href="https://wa.me/5511987654321"', false);
    }

    public function test_base_de_renovacao_excludes_certificates_outside_the_30_day_window(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create();
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'certificate_expires_at' => now()->addDays(60),
        ]);

        $response = $this->get('/painel/relatorios/');

        $response->assertSee('Nenhum certificado vencendo nos próximos 30 dias');
    }
}
