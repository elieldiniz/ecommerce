<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\CertificateFormat;
use App\Models\Coupon;
use App\Models\CouponType;
use App\Models\CouponUse;
use App\Models\Customer;
use App\Models\GfsisStatus;
use App\Models\HolderType;
use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\RefundReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        foreach (['vendas-por-periodo', 'vendas-por-produto', 'funil-operacional', 'pagos-sem-dados', 'base-de-renovacao', 'atribuicao', 'conciliacao-do-gateway', 'estornos', 'cupons'] as $key) {
            preg_match('/<button[^>]*data-report="'.$key.'"[^>]*>/s', $content, $buttonMatch);
            $this->assertStringContainsString('cursor-pointer', $buttonMatch[0] ?? '', "Card '{$key}' não tem cursor-pointer.");
        }
    }

    public function test_clicking_base_de_renovacao_card_switches_active_report_and_hides_vendas_por_periodo(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'base-de-renovacao');

        $component->assertSee('Titular');
        $component->assertSee('Documento');
        $component->assertDontSee('Gráfico de linha · faturamento diário');

        $content = $component->html();
        preg_match('/<button[^>]*data-report="base-de-renovacao"[^>]*>/s', $content, $matches);
        $this->assertStringContainsString('border-2 border-brand bg-highlight', $matches[0] ?? '');
        $this->assertSame(1, substr_count($content, 'border-2 border-brand bg-highlight'));
    }

    public function test_switching_active_report_does_not_reset_already_selected_filters(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.relatorios')
            ->set('periodo', '7d')
            ->set('activeReport', 'base-de-renovacao')
            ->assertSet('periodo', '7d');
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

    public function test_origem_filter_restricts_vendas_por_periodo_to_the_selected_utm_source(): void
    {
        $this->actingAs(User::factory()->create());

        $orderFacebook = $this->paidOrder(['total' => 777]);
        OrderAttribution::factory()->create(['order_id' => $orderFacebook->id, 'utm_source' => 'facebook']);

        $orderGoogle = $this->paidOrder(['total' => 888]);
        OrderAttribution::factory()->create(['order_id' => $orderGoogle->id, 'utm_source' => 'google']);

        $filteredByGoogle = Livewire::test('pages::painel.relatorios')
            ->set('origem', 'google')
            ->instance()
            ->filteredPaidOrders;
        $this->assertSame(['888.00'], $filteredByGoogle->pluck('total')->all());

        $filteredByAll = Livewire::test('pages::painel.relatorios')
            ->set('origem', '')
            ->instance()
            ->filteredPaidOrders;
        $this->assertEqualsCanonicalizing(['777.00', '888.00'], $filteredByAll->pluck('total')->all());
    }

    public function test_vendas_por_produto_compares_the_two_real_product_categories(): void
    {
        $this->actingAs(User::factory()->create());

        $ecpf = Product::factory()->create(['name' => 'Certificado Digital e-CPF']);
        $ecnpj = Product::factory()->create(['name' => 'Certificado Digital e-CNPJ']);
        $variantCpf = ProductVariant::factory()->create(['product_id' => $ecpf->id]);
        $variantCnpj = ProductVariant::factory()->create(['product_id' => $ecnpj->id]);

        $orderCpf = $this->paidOrder(['total' => 100]);
        OrderItem::factory()->create(['order_id' => $orderCpf->id, 'product_variant_id' => $variantCpf->id, 'quantity' => 1, 'total' => 100]);

        $orderCnpj = $this->paidOrder(['total' => 200]);
        OrderItem::factory()->create(['order_id' => $orderCnpj->id, 'product_variant_id' => $variantCnpj->id, 'quantity' => 1, 'total' => 200]);

        // Pedido de e-CNPJ comprado por um MEI: soma na mesma linha "e-CNPJ", sem categoria própria.
        $orderMei = $this->paidOrder(['total' => 50]);
        OrderItem::factory()->create(['order_id' => $orderMei->id, 'product_variant_id' => $variantCnpj->id, 'quantity' => 1, 'total' => 50]);

        $rows = Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'vendas-por-produto')
            ->instance()
            ->vendasPorProduto;

        $this->assertCount(2, $rows);

        $cpfRow = $rows->firstWhere('produto', 'Certificado Digital e-CPF');
        $cnpjRow = $rows->firstWhere('produto', 'Certificado Digital e-CNPJ');

        $this->assertSame(100.0, $cpfRow['faturamento']);
        $this->assertSame(250.0, $cnpjRow['faturamento']);
        $this->assertNull($rows->firstWhere('produto', 'MEI'));
    }

    public function test_vendas_por_produto_filters_to_a_single_product_line_when_produto_filter_is_set(): void
    {
        $this->actingAs(User::factory()->create());

        $productA = Product::factory()->create(['name' => 'Produto A']);
        $productB = Product::factory()->create(['name' => 'Produto B']);
        $variantA = ProductVariant::factory()->create(['product_id' => $productA->id]);
        $variantB = ProductVariant::factory()->create(['product_id' => $productB->id]);

        $order = $this->paidOrder(['total' => 300]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_variant_id' => $variantA->id, 'quantity' => 1, 'total' => 100]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_variant_id' => $variantB->id, 'quantity' => 1, 'total' => 200]);

        $rows = Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'vendas-por-produto')
            ->set('produto', $productA->id)
            ->instance()
            ->vendasPorProduto;

        $this->assertCount(1, $rows);
        $this->assertSame('Produto A', $rows->first()['produto']);
    }

    public function test_vendas_por_produto_query_count_stays_constant_regardless_of_product_count(): void
    {
        $this->actingAs(User::factory()->create());

        $order = $this->paidOrder(['total' => 100]);
        for ($i = 0; $i < 2; $i++) {
            $variant = ProductVariant::factory()->create(['product_id' => Product::factory()->create()->id]);
            OrderItem::factory()->create(['order_id' => $order->id, 'product_variant_id' => $variant->id, 'quantity' => 1, 'total' => 10]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'vendas-por-produto')->instance()->vendasPorProduto;
        $twoProductsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $order2 = $this->paidOrder(['total' => 100]);
        for ($i = 0; $i < 10; $i++) {
            $variant = ProductVariant::factory()->create(['product_id' => Product::factory()->create()->id]);
            OrderItem::factory()->create(['order_id' => $order2->id, 'product_variant_id' => $variant->id, 'quantity' => 1, 'total' => 10]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'vendas-por-produto')->instance()->vendasPorProduto;
        $twelveProductsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($twoProductsQueryCount, $twelveProductsQueryCount);
    }

    public function test_funil_operacional_excludes_orders_outside_the_active_period_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $emitido = GfsisStatus::factory()->create(['slug' => 'emitido']);

        $insideEmitido = $this->paidOrder(['paid_at' => now(), 'created_at' => now()]);
        $item = OrderItem::factory()->create(['order_id' => $insideEmitido->id]);
        OrderItemGfsis::factory()->create(['order_item_id' => $item->id, 'status_id' => $emitido->id]);

        $this->paidOrder(['paid_at' => now(), 'created_at' => now()]);

        for ($i = 0; $i < 3; $i++) {
            $this->paidOrder(['paid_at' => now()->subDays(60), 'created_at' => now()->subDays(60)]);
        }

        $stages = Livewire::test('pages::painel.relatorios')
            ->set('periodo', '30d')
            ->instance()
            ->funilOperacionalFiltrado;

        $stages = collect($stages)->keyBy('name');
        $this->assertSame(2, $stages['Pedidos criados']['quantity']);
        $this->assertSame(2, $stages['Pagos']['quantity']);
        $this->assertSame(1, $stages['Emitidos']['quantity']);
    }

    public function test_funil_percentages_remain_step_by_step_conversion(): void
    {
        $this->actingAs(User::factory()->create());

        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        for ($i = 0; $i < 4; $i++) {
            $this->paidOrder();
        }
        $this->paidOrder(['fulfillment_status_id' => $awaitingData->id]);

        $stages = Livewire::test('pages::painel.relatorios')
            ->instance()
            ->funilOperacionalFiltrado;

        $stages = collect($stages)->keyBy('name');
        // 5 pagos, 4 com dados completos: conversão passo a passo = 4/5 = 80%, não 4/5 dos criados.
        $this->assertSame('80%', $stages['Dados completos']['percentage']);
    }

    public function test_selecting_a_period_in_vendas_por_periodo_stays_applied_after_switching_to_funil_operacional(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.relatorios')
            ->set('periodo', '7d')
            ->set('activeReport', 'funil-operacional')
            ->assertSet('periodo', '7d')
            ->assertSet('activeReport', 'funil-operacional');
    }

    public function test_pagos_sem_dados_shows_orders_matching_status_and_fulfillment_within_the_active_filters(): void
    {
        $this->actingAs(User::factory()->create());

        $awaitingData = $this->fulfillmentStatus('awaiting_data');
        $productA = Product::factory()->create(['name' => 'Produto A']);
        $productB = Product::factory()->create(['name' => 'Produto B']);
        $variantA = ProductVariant::factory()->create(['product_id' => $productA->id]);
        $variantB = ProductVariant::factory()->create(['product_id' => $productB->id]);

        $matching = $this->paidOrder(['fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()]);
        OrderItem::factory()->create(['order_id' => $matching->id, 'product_variant_id' => $variantA->id]);

        // Fora do período filtrado — não deve aparecer.
        $outsidePeriod = $this->paidOrder(['fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(60)]);
        OrderItem::factory()->create(['order_id' => $outsidePeriod->id, 'product_variant_id' => $variantA->id]);

        // Produto diferente do filtro — não deve aparecer quando "Produto" é setado.
        $otherProduct = $this->paidOrder(['fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()]);
        OrderItem::factory()->create(['order_id' => $otherProduct->id, 'product_variant_id' => $variantB->id]);

        $orders = Livewire::test('pages::painel.relatorios')
            ->set('periodo', '30d')
            ->set('produto', $productA->id)
            ->instance()
            ->pagosSemDadosFiltrado;

        $this->assertCount(1, $orders);
        $this->assertSame($matching->id, $orders->first()->id);
    }

    public function test_pagos_sem_dados_query_count_stays_constant_regardless_of_order_count(): void
    {
        $this->actingAs(User::factory()->create());

        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        for ($i = 0; $i < 2; $i++) {
            $order = $this->paidOrder(['fulfillment_status_id' => $awaitingData->id]);
            OrderItem::factory()->create(['order_id' => $order->id]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'pagos-sem-dados')->instance()->pagosSemDadosFiltrado;
        $twoOrdersQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        for ($i = 0; $i < 8; $i++) {
            $order = $this->paidOrder(['fulfillment_status_id' => $awaitingData->id]);
            OrderItem::factory()->create(['order_id' => $order->id]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'pagos-sem-dados')->instance()->pagosSemDadosFiltrado;
        $tenOrdersQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($twoOrdersQueryCount, $tenOrdersQueryCount);
    }

    public function test_atribuicao_groups_orders_with_attribution_by_utm_source(): void
    {
        $this->actingAs(User::factory()->create());

        $order = $this->paidOrder(['total' => 150]);
        OrderAttribution::factory()->create(['order_id' => $order->id, 'utm_source' => 'google']);

        $rows = Livewire::test('pages::painel.relatorios')->instance()->atribuicao;

        $googleRow = $rows->firstWhere('origem', 'google');
        $this->assertNotNull($googleRow);
        $this->assertSame(1, $googleRow['pedidos']);
        $this->assertSame(150.0, $googleRow['faturamento']);
    }

    public function test_atribuicao_falls_back_to_direto_nao_identificado_when_attribution_is_missing(): void
    {
        $this->actingAs(User::factory()->create());

        $this->paidOrder(['total' => 200]);

        $rows = Livewire::test('pages::painel.relatorios')->instance()->atribuicao;

        $directRow = $rows->firstWhere('origem', 'Direto/Não identificado');
        $this->assertNotNull($directRow);
        $this->assertSame(1, $directRow['pedidos']);
    }

    public function test_atribuicao_query_count_stays_constant_regardless_of_origin_count(): void
    {
        $this->actingAs(User::factory()->create());

        for ($i = 0; $i < 2; $i++) {
            $order = $this->paidOrder();
            OrderAttribution::factory()->create(['order_id' => $order->id, 'utm_source' => "origem-{$i}"]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'atribuicao')->instance()->atribuicao;
        $twoOriginsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        for ($i = 0; $i < 8; $i++) {
            $order = $this->paidOrder();
            OrderAttribution::factory()->create(['order_id' => $order->id, 'utm_source' => "origem-extra-{$i}"]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'atribuicao')->instance()->atribuicao;
        $tenOriginsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($twoOriginsQueryCount, $tenOriginsQueryCount);
    }

    public function test_conciliacao_do_gateway_shows_an_explicit_unavailable_state_without_fake_data(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'conciliacao-do-gateway')
            ->html();

        $this->assertStringContainsString('Relatório indisponível', $html);
        $this->assertStringContainsString('gateway_fee', $html);

        preg_match('/Conciliação do gateway<\/h2>(.*?)<\/section>/s', $html, $matches);
        $this->assertNotEmpty($matches, 'Seção "Conciliação do gateway" não encontrada.');
        $this->assertStringNotContainsString('<table', $matches[1]);
        $this->assertStringNotContainsString('rounded-xl border border-border bg-white p-4.5', $matches[1]);
    }

    public function test_estornos_shows_refunds_from_orders_matching_the_active_filters_with_the_real_reason_name(): void
    {
        $this->actingAs(User::factory()->create());

        $reason = RefundReason::factory()->create(['name' => 'Arrependimento']);

        $insideOrder = $this->paidOrder(['paid_at' => now()]);
        $insidePayment = Payment::factory()->create(['order_id' => $insideOrder->id]);
        Refund::factory()->create(['payment_id' => $insidePayment->id, 'reason_id' => $reason->id, 'amount' => 90]);

        $outsideOrder = $this->paidOrder(['paid_at' => now()->subDays(60)]);
        $outsidePayment = Payment::factory()->create(['order_id' => $outsideOrder->id]);
        Refund::factory()->create(['payment_id' => $outsidePayment->id, 'reason_id' => $reason->id, 'amount' => 50]);

        $refunds = Livewire::test('pages::painel.relatorios')
            ->set('periodo', '30d')
            ->instance()
            ->estornos;

        $this->assertCount(1, $refunds);
        $this->assertSame('Arrependimento', $refunds->first()->reason->name);
        $this->assertSame($insideOrder->id, $refunds->first()->payment->order_id);
    }

    public function test_clicking_the_estornos_card_switches_active_report_and_hides_vendas_por_periodo_and_base_de_renovacao(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test('pages::painel.relatorios')->set('activeReport', 'estornos');

        $component->assertSee('Motivo');
        $component->assertDontSee('Gráfico de linha · faturamento diário');
        $component->assertDontSee('Titular');

        $content = $component->html();
        $this->assertSame(1, substr_count($content, 'border-2 border-brand bg-highlight'));
        preg_match('/<button[^>]*data-report="estornos"[^>]*>/s', $content, $matches);
        $this->assertStringContainsString('border-2 border-brand bg-highlight', $matches[0] ?? '');
    }

    public function test_estornos_query_count_stays_constant_regardless_of_refund_count(): void
    {
        $this->actingAs(User::factory()->create());

        $reason = RefundReason::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            $order = $this->paidOrder();
            $payment = Payment::factory()->create(['order_id' => $order->id]);
            Refund::factory()->create(['payment_id' => $payment->id, 'reason_id' => $reason->id]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'estornos')->instance()->estornos;
        $twoRefundsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        for ($i = 0; $i < 8; $i++) {
            $order = $this->paidOrder();
            $payment = Payment::factory()->create(['order_id' => $order->id]);
            Refund::factory()->create(['payment_id' => $payment->id, 'reason_id' => $reason->id]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'estornos')->instance()->estornos;
        $tenRefundsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($twoRefundsQueryCount, $tenRefundsQueryCount);
    }

    public function test_cupons_aggregates_uses_and_discount_within_the_active_period_filter(): void
    {
        $this->actingAs(User::factory()->create());

        $type = CouponType::factory()->create(['name' => 'Percentual']);
        $coupon = Coupon::factory()->create(['code' => 'PROMO10', 'type_id' => $type->id]);

        $orderInside1 = $this->paidOrder(['paid_at' => now(), 'coupon_id' => $coupon->id]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'order_id' => $orderInside1->id, 'discount_applied' => 10]);

        $orderInside2 = $this->paidOrder(['paid_at' => now(), 'coupon_id' => $coupon->id]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'order_id' => $orderInside2->id, 'discount_applied' => 15]);

        $orderOutside = $this->paidOrder(['paid_at' => now()->subDays(60), 'coupon_id' => $coupon->id]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'order_id' => $orderOutside->id, 'discount_applied' => 20]);

        $rows = Livewire::test('pages::painel.relatorios')
            ->set('periodo', '30d')
            ->instance()
            ->cuponsUsados;

        $row = $rows->firstWhere('codigo', 'PROMO10');
        $this->assertSame(2, $row['usos']);
        $this->assertSame(25.0, $row['desconto_total']);
    }

    public function test_filling_busca_and_switching_to_cupons_does_not_filter_the_coupon_list(): void
    {
        $this->actingAs(User::factory()->create());

        $type = CouponType::factory()->create();
        $coupon = Coupon::factory()->create(['code' => 'FIXO20', 'type_id' => $type->id]);
        $order = $this->paidOrder(['coupon_id' => $coupon->id]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'order_id' => $order->id]);

        $rows = Livewire::test('pages::painel.relatorios')
            ->set('busca', 'nada-que-bata-com-nenhum-cupom')
            ->instance()
            ->cuponsUsados;

        $this->assertNotNull($rows->firstWhere('codigo', 'FIXO20'));
    }

    public function test_with_cupons_active_exactly_one_of_the_nine_cards_has_the_highlight_class(): void
    {
        $this->actingAs(User::factory()->create());

        $content = Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'cupons')
            ->html();

        $this->assertSame(1, substr_count($content, 'border-2 border-brand bg-highlight'));
        preg_match('/<button[^>]*data-report="cupons"[^>]*>/s', $content, $matches);
        $this->assertStringContainsString('border-2 border-brand bg-highlight', $matches[0] ?? '');
    }

    public function test_cupons_query_count_stays_constant_regardless_of_coupon_count(): void
    {
        $this->actingAs(User::factory()->create());

        $type = CouponType::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            $coupon = Coupon::factory()->create(['type_id' => $type->id]);
            $order = $this->paidOrder();
            CouponUse::factory()->create(['coupon_id' => $coupon->id, 'order_id' => $order->id]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'cupons')->instance()->cuponsUsados;
        $twoCouponsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        for ($i = 0; $i < 8; $i++) {
            $coupon = Coupon::factory()->create(['type_id' => $type->id]);
            $order = $this->paidOrder();
            CouponUse::factory()->create(['coupon_id' => $coupon->id, 'order_id' => $order->id]);
        }

        DB::enableQueryLog();
        Livewire::test('pages::painel.relatorios')->set('activeReport', 'cupons')->instance()->cuponsUsados;
        $tenCouponsQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($twoCouponsQueryCount, $tenCouponsQueryCount);
    }

    public function test_exportar_csv_for_cupons_returns_csv_with_the_coupon_code_and_aggregated_discount(): void
    {
        $this->actingAs(User::factory()->create());

        $type = CouponType::factory()->create();
        $coupon = Coupon::factory()->create(['code' => 'EXPORT10', 'type_id' => $type->id]);
        $order = $this->paidOrder(['coupon_id' => $coupon->id]);
        CouponUse::factory()->create(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'discount_applied' => 12.5]);

        Livewire::test('pages::painel.relatorios')->call('exportarCsv', 'cupons')->assertOk();

        $response = Livewire::test('pages::painel.relatorios')->instance()->exportarCsv('cupons');
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('EXPORT10', $csv);
        $this->assertStringContainsString('12.50', $csv);
    }

    public function test_exportar_csv_filename_is_prefixed_by_the_report_key(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['vendas-por-produto', 'estornos'] as $key) {
            $response = Livewire::test('pages::painel.relatorios')->instance()->exportarCsv($key);

            $disposition = $response->headers->get('Content-Disposition');
            $this->assertStringContainsString("{$key}-", (string) $disposition);
        }
    }

    public function test_no_exportar_pdf_button_has_a_wire_click_attribute_in_any_of_the_nine_report_sections(): void
    {
        $this->actingAs(User::factory()->create());

        $keys = [
            'vendas-por-periodo', 'vendas-por-produto', 'funil-operacional', 'pagos-sem-dados',
            'base-de-renovacao', 'atribuicao', 'conciliacao-do-gateway', 'estornos', 'cupons',
        ];

        foreach ($keys as $key) {
            $html = Livewire::test('pages::painel.relatorios')->set('activeReport', $key)->html();

            preg_match_all('/<button[^>]*>Exportar PDF<\/button>/s', $html, $matches);

            foreach ($matches[0] as $button) {
                $this->assertStringNotContainsString('wire:click', $button, "Botão 'Exportar PDF' da seção '{$key}' não deveria ter wire:click.");
            }
        }
    }

    public function test_filter_bar_renders_outside_any_report_specific_section(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'vendas-por-periodo')
            ->assertSee('Filtros');

        Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'base-de-renovacao')
            ->assertSee('Filtros');
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

        $component = Livewire::test('pages::painel.relatorios')->set('activeReport', 'base-de-renovacao');

        $component->assertSeeInOrder(['Titular', 'Documento', 'Produto', 'Vence em', 'Dias', 'Contato']);
        $component->assertSee('Carlos Eduardo Nogueira');
        $component->assertSee('555.666.777-88');
        $component->assertSee('Certificado Digital e-CPF A1');
        $component->assertSee(now()->addDays(21)->format('d/m/Y'));
        $component->assertSee('href="https://wa.me/5511987654321"', false);
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

        Livewire::test('pages::painel.relatorios')
            ->set('activeReport', 'base-de-renovacao')
            ->assertSee('Nenhum certificado vencendo nos próximos 30 dias');
    }
}
