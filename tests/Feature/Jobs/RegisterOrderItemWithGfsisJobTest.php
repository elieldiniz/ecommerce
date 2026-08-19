<?php

namespace Tests\Feature\Jobs;

use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Models\GfsisStatus;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\OrderStatus;
use App\Models\ProductVariant;
use App\Models\Setting;
use Database\Seeders\GfsisStatusSeeder;
use Database\Seeders\OrderFulfillmentStatusSeeder;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterOrderItemWithGfsisJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OrderStatusSeeder::class);
        $this->seed(OrderFulfillmentStatusSeeder::class);
        $this->seed(GfsisStatusSeeder::class);

        Setting::factory()->create(['key' => 'gfsis_ponto_atendimento', 'value' => '10', 'group' => 'gfsis']);
        Setting::factory()->create(['key' => 'gfsis_tipo_validacao', 'value' => '2', 'group' => 'gfsis']);

        config([
            'services.gfsis.base_url' => 'https://gfsis.example.com',
            'services.gfsis.login' => 'integracao-login',
            'services.gfsis.senha' => 'integracao-senha',
        ]);
    }

    private function makeOrder(string $orderStatusSlug, string $fulfillmentStatusSlug): Order
    {
        return Order::factory()->create([
            'status_id' => OrderStatus::query()->where('slug', $orderStatusSlug)->value('id'),
            'fulfillment_status_id' => OrderFulfillmentStatus::query()->where('slug', $fulfillmentStatusSlug)->value('id'),
        ]);
    }

    private function makeOrderItem(Order $order, ?int $gfsisCertificadoId = 55): OrderItem
    {
        $productVariant = ProductVariant::factory()->create([
            'gfsis_certificado_id' => $gfsisCertificadoId,
            'price' => 249.90,
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_variant_id' => $productVariant->id,
            'unit_price' => 249.90,
        ]);

        IssuanceData::factory()->create([
            'order_item_id' => $orderItem->id,
            'document' => '12345678900',
        ]);

        return $orderItem;
    }

    private function fakeCriarPedidoVenda(array $response, int $status = 201): void
    {
        Http::fake([
            '*/gestaofacil/rest/auth' => Http::response([
                'acessToken' => 'token-1',
                'expirationDate' => now()->addMinutes(30)->format('Y-m-d H:i'),
            ], 200),
            '*/gestaofacil/rest/CriaPedidoVendaLTS' => Http::response($response, $status),
        ]);
    }

    public function test_an_order_not_paid_never_calls_the_gfsis_api_even_with_complete_issuance_data(): void
    {
        $order = $this->makeOrder('awaiting_payment', 'data_complete');
        $this->makeOrderItem($order);

        Http::fake();

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        Http::assertNothingSent();
        $this->assertSame(0, OrderItemGfsis::query()->count());
    }

    public function test_an_order_paid_but_not_data_complete_never_calls_the_gfsis_api(): void
    {
        $order = $this->makeOrder('paid', 'awaiting_data');
        $this->makeOrderItem($order);

        Http::fake();

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        Http::assertNothingSent();
        $this->assertSame(0, OrderItemGfsis::query()->count());
    }

    public function test_missing_gfsis_certificado_id_blocks_before_any_http_call_and_records_last_error(): void
    {
        $order = $this->makeOrder('paid', 'data_complete');
        $this->makeOrderItem($order, gfsisCertificadoId: null);

        Http::fake();

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        Http::assertNothingSent();

        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();
        $this->assertNotNull($orderItemGfsis->last_error);
        $this->assertSame(0, $orderItemGfsis->attempts);
    }

    public function test_a_pf_order_item_with_missing_birth_date_sends_the_http_call_without_local_blocking(): void
    {
        $order = $this->makeOrder('paid', 'data_complete');
        $orderItem = $this->makeOrderItem($order);
        $orderItem->issuanceData->update(['birth_date' => null]);

        $this->fakeCriarPedidoVenda(['erro' => false, 'codigo' => '102930']);

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS'));

        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();
        $this->assertSame('102930', $orderItemGfsis->gfsis_code);
    }

    public function test_a_201_success_response_records_gfsis_code_and_moves_status_to_enviado_gfsis_and_sent_to_gfsis(): void
    {
        $order = $this->makeOrder('paid', 'data_complete');
        $this->makeOrderItem($order);

        $this->fakeCriarPedidoVenda(['erro' => false, 'codigo' => '102930', 'mensagem' => 'ok', 'urlPagamento' => 'https://x'], 201);

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();

        $this->assertSame('102930', $orderItemGfsis->gfsis_code);
        $this->assertSame('enviado_gfsis', $orderItemGfsis->status->slug);
        $this->assertSame(1, $orderItemGfsis->attempts);
        $this->assertNotNull($orderItemGfsis->sent_at);

        $this->assertSame('sent_to_gfsis', $order->fresh()->fulfillmentStatus->slug);
    }

    public function test_two_executions_for_the_same_order_item_send_the_same_pedido_id(): void
    {
        $order = $this->makeOrder('paid', 'data_complete');
        $this->makeOrderItem($order);

        $this->fakeCriarPedidoVenda(['erro' => true, 'codigo' => '999', 'mensagem' => 'Erro inesperado'], 500);

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        $firstGfsisOrderId = OrderItemGfsis::query()->firstOrFail()->gfsis_order_id;
        $this->assertSame('send_failed', $order->fresh()->fulfillmentStatus->slug);

        // Reprocessamento direto: `send_failed` já é retentável, sem reset manual de status.
        (new RegisterOrderItemWithGfsisJob($order))->handle();

        $secondGfsisOrderId = OrderItemGfsis::query()->firstOrFail()->gfsis_order_id;

        $this->assertSame($firstGfsisOrderId, $secondGfsisOrderId);
        $this->assertSame(1, OrderItemGfsis::query()->count());

        $pedidoRequests = Http::recorded(
            fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS')
        )->values();

        $this->assertCount(2, $pedidoRequests);
        $this->assertSame($firstGfsisOrderId, $pedidoRequests[0][0]->data()['pedido']['id']);
        $this->assertSame($firstGfsisOrderId, $pedidoRequests[1][0]->data()['pedido']['id']);
    }

    public function test_error_code_005_records_last_error_increments_attempts_and_moves_fulfillment_status_to_send_failed(): void
    {
        $order = $this->makeOrder('paid', 'data_complete');
        $this->makeOrderItem($order);

        $this->fakeCriarPedidoVenda(['erro' => true, 'codigo' => '005', 'mensagem' => 'Certificado inválido'], 400);

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();

        $this->assertNotNull($orderItemGfsis->last_error);
        $this->assertSame(1, $orderItemGfsis->attempts);
        $this->assertSame('send_failed', $order->fresh()->fulfillmentStatus->slug);
    }

    public function test_a_send_failed_order_is_retryable_directly_and_moves_out_of_failure_on_success(): void
    {
        $order = $this->makeOrder('paid', 'send_failed');
        $orderItem = $this->makeOrderItem($order);

        OrderItemGfsis::factory()->create([
            'order_item_id' => $orderItem->id,
            'status_id' => GfsisStatus::query()->where('slug', 'falha_envio')->value('id'),
            'last_error' => 'CPF/CNPJ inválido',
            'attempts' => 2,
        ]);

        $this->fakeCriarPedidoVenda(['erro' => false, 'codigo' => '102930', 'mensagem' => 'ok', 'urlPagamento' => 'https://x'], 201);

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS'));
        $this->assertSame('enviado_gfsis', $orderItemGfsis->status->slug);
        $this->assertSame(3, $orderItemGfsis->attempts);
        $this->assertSame('sent_to_gfsis', $order->fresh()->fulfillmentStatus->slug);
    }

    public function test_a_send_failed_order_retried_directly_that_fails_again_stays_failed_and_records_the_new_error(): void
    {
        $order = $this->makeOrder('paid', 'send_failed');
        $orderItem = $this->makeOrderItem($order);

        OrderItemGfsis::factory()->create([
            'order_item_id' => $orderItem->id,
            'status_id' => GfsisStatus::query()->where('slug', 'falha_envio')->value('id'),
            'last_error' => 'CPF/CNPJ inválido',
            'attempts' => 2,
        ]);

        $this->fakeCriarPedidoVenda(['erro' => true, 'codigo' => '999', 'mensagem' => 'Erro inesperado'], 500);

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS'));
        $this->assertSame('falha_envio', $orderItemGfsis->status->slug);
        $this->assertSame(3, $orderItemGfsis->attempts);
        $this->assertNotSame('CPF/CNPJ inválido', $orderItemGfsis->last_error);
        $this->assertSame('send_failed', $order->fresh()->fulfillmentStatus->slug);
    }

    public function test_error_code_002_does_not_change_last_error_or_attempts_and_applies_success(): void
    {
        $order = $this->makeOrder('paid', 'data_complete');
        $this->makeOrderItem($order);

        $orderItemGfsis = OrderItemGfsis::factory()->create([
            'order_item_id' => $order->items->first()->id,
            'attempts' => 3,
            'last_error' => null,
        ]);

        $this->fakeCriarPedidoVenda(['erro' => true, 'codigo' => '002', 'mensagem' => 'Pedido já registrado'], 400);

        (new RegisterOrderItemWithGfsisJob($order))->handle();

        $orderItemGfsis->refresh();

        $this->assertNull($orderItemGfsis->last_error);
        $this->assertSame(3, $orderItemGfsis->attempts);
        $this->assertSame('enviado_gfsis', $orderItemGfsis->status->slug);
        $this->assertSame('sent_to_gfsis', $order->fresh()->fulfillmentStatus->slug);
    }
}
