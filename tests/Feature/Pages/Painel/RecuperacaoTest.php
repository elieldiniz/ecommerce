<?php

namespace Tests\Feature\Pages\Painel;

use App\Actions\Gfsis\GenerateIssuanceAccessToken;
use App\Mail\IssuanceAccessLinkMail;
use App\Models\Customer;
use App\Models\GfsisStatus;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\OrderStatus;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\GfsisStatusSeeder;
use Database\Seeders\OrderFulfillmentStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RecuperacaoTest extends TestCase
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

    private function gfsisStatus(string $slug): GfsisStatus
    {
        return GfsisStatus::where('slug', $slug)->first()
            ?? GfsisStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    /**
     * Configura credenciais/settings do GFSIS e cria um order_item com
     * `issuance_data` completo e `gfsis_certificado_id` configurado, prontos
     * para uma chamada real a `CriaPedidoVendaLTS` (mesmo setup de
     * `RegisterOrderItemWithGfsisJobTest`).
     */
    private function makeFalhaEnvioOrder(): Order
    {
        $this->seed(OrderFulfillmentStatusSeeder::class);
        $this->seed(GfsisStatusSeeder::class);

        Setting::factory()->create(['key' => 'gfsis_ponto_atendimento', 'value' => '10', 'group' => 'gfsis']);
        Setting::factory()->create(['key' => 'gfsis_tipo_validacao', 'value' => '2', 'group' => 'gfsis']);

        config([
            'services.gfsis.base_url' => 'https://gfsis.example.com',
            'services.gfsis.login' => 'integracao-login',
            'services.gfsis.senha' => 'integracao-senha',
        ]);

        $order = Order::factory()->create([
            'number' => 'PED-FALHOU1',
            'status_id' => $this->orderStatus('paid')->id,
            'fulfillment_status_id' => $this->fulfillmentStatus('send_failed')->id,
        ]);

        $productVariant = ProductVariant::factory()->create(['gfsis_certificado_id' => 55]);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'product_variant_id' => $productVariant->id]);
        IssuanceData::factory()->create(['order_item_id' => $item->id]);

        OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'status_id' => $this->gfsisStatus('falha_envio')->id,
            'last_error' => 'CPF/CNPJ inválido',
            'attempts' => 2,
        ]);

        return $order;
    }

    private function fakeCriarPedidoVenda(array $response, int $status): void
    {
        Http::fake([
            '*/gestaofacil/rest/auth' => Http::response([
                'acessToken' => 'token-1',
                'expirationDate' => now()->addMinutes(30)->format('Y-m-d H:i'),
            ], 200),
            '*/gestaofacil/rest/CriaPedidoVendaLTS' => Http::response($response, $status),
        ]);
    }

    private function makeQueuedOrder(): Order
    {
        $order = Order::factory()->create([
            'status_id' => $this->orderStatus('paid')->id,
            'fulfillment_status_id' => $this->fulfillmentStatus('awaiting_data')->id,
            'paid_at' => now()->subDays(2),
        ]);

        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order->fresh();
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/recuperacao/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_view(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::painel.recuperacao')->assertOk();
    }

    public function test_block_indicadores_com_fila_vazia(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('Pagos sem dados');
        $response->assertSee('Recuperados em 7 dias');
        $response->assertSee('Mais antigo');
        $response->assertSee('Falha de envio');
        $response->assertSee('—');
    }

    public function test_kpi_pagos_sem_dados_reflete_contagem_real(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makeQueuedOrder();
        $this->makeQueuedOrder();

        Livewire::test('pages::painel.recuperacao')
            ->assertSee('2');
    }

    public function test_kpi_recuperados_7_dias_calcula_percentual(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $dataComplete = $this->fulfillmentStatus('data_complete');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $dataComplete->id, 'paid_at' => now()->subDays(1)]);
        Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(1)]);

        Livewire::test('pages::painel.recuperacao')
            ->assertSee('50%');
    }

    public function test_table_fila_ordenada_por_paid_at_sem_coluna_contatos(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        $antigo = Order::factory()->create(['number' => 'PED-ANTIGO0', 'status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(5)]);
        OrderItem::factory()->create(['order_id' => $antigo->id]);

        $recente = Order::factory()->create(['number' => 'PED-RECENTE', 'status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(1)]);
        OrderItem::factory()->create(['order_id' => $recente->id]);

        $response = $this->get('/painel/recuperacao/');

        $response->assertSeeInOrder(['PED-ANTIGO0', 'PED-RECENTE']);
        $response->assertDontSee('Contatos');
    }

    public function test_reenviar_link_regenera_token_e_envia_email(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());

        $order = $this->makeQueuedOrder();
        (new GenerateIssuanceAccessToken)->execute($order);
        $tokenAntigo = IssuanceData::query()->firstOrFail()->access_token;

        Livewire::test('pages::painel.recuperacao')
            ->call('resendLink', $order->id)
            ->assertOk();

        $this->assertNotSame($tokenAntigo, IssuanceData::query()->firstOrFail()->access_token);
        Mail::assertSent(IssuanceAccessLinkMail::class);
    }

    public function test_reenviar_link_com_email_malformado_nao_quebra_a_pagina_e_avisa_a_falha(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create(['email' => 'eliel diniz1@outl.com']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status_id' => $this->orderStatus('paid')->id,
            'fulfillment_status_id' => $this->fulfillmentStatus('awaiting_data')->id,
            'paid_at' => now()->subDays(2),
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        Livewire::test('pages::painel.recuperacao')
            ->call('resendLink', $order->id)
            ->assertOk();
    }

    public function test_table_regua_automatica_permanece_estatica(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('Régua automática');
        $response->assertSeeInOrder(['Imediato', 'E-mail']);
        $response->assertSeeInOrder(['2 horas', 'WhatsApp']);
        $response->assertSeeInOrder(['24 horas', 'E-mail']);
        $response->assertSeeInOrder(['3 dias', 'WhatsApp']);
        $response->assertSeeInOrder(['5 dias', 'Painel']);
    }

    public function test_block_falhas_de_integracao_exibe_last_error_e_attempts(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create(['number' => 'PED-FALHOU1']);
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'status_id' => $this->gfsisStatus('falha_envio')->id,
            'last_error' => 'Timeout ao enviar dados ao GFSIS',
            'attempts' => 3,
        ]);

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('PED-FALHOU1');
        $response->assertSee('Timeout ao enviar dados ao GFSIS');
        $response->assertSee('Corrigir e reenviar');
    }

    public function test_corrigir_e_reenviar_com_sucesso_tira_o_pedido_da_fila_de_falhas(): void
    {
        $this->actingAs(User::factory()->create());

        $order = $this->makeFalhaEnvioOrder();
        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();

        $this->fakeCriarPedidoVenda(['erro' => false, 'codigo' => '102930', 'mensagem' => 'ok', 'urlPagamento' => 'https://x'], 201);

        Livewire::test('pages::painel.recuperacao')
            ->call('fixAndResend', $orderItemGfsis->id)
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS'));

        $orderItemGfsis->refresh();
        $this->assertSame('enviado_gfsis', $orderItemGfsis->status->slug);
        $this->assertSame('sent_to_gfsis', $order->fresh()->fulfillmentStatus->slug);

        Livewire::test('pages::painel.recuperacao')->assertDontSee($order->number);
    }

    public function test_corrigir_e_reenviar_que_falha_de_novo_permanece_na_fila_com_o_novo_erro(): void
    {
        $this->actingAs(User::factory()->create());

        $order = $this->makeFalhaEnvioOrder();
        $orderItemGfsis = OrderItemGfsis::query()->firstOrFail();

        $this->fakeCriarPedidoVenda(['erro' => true, 'codigo' => '999', 'mensagem' => 'Erro inesperado'], 500);

        Livewire::test('pages::painel.recuperacao')
            ->call('fixAndResend', $orderItemGfsis->id)
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS'));

        $orderItemGfsis->refresh();
        $this->assertSame('falha_envio', $orderItemGfsis->status->slug);
        $this->assertSame(3, $orderItemGfsis->attempts);
        $this->assertNotSame('CPF/CNPJ inválido', $orderItemGfsis->last_error);
        $this->assertSame('send_failed', $order->fresh()->fulfillmentStatus->slug);

        $response = $this->get('/painel/recuperacao/');
        $response->assertSee($order->number);
    }

    public function test_botoes_de_acao_ficam_desabilitados_durante_requisicao(): void
    {
        $this->actingAs(User::factory()->create());

        $order = $this->makeQueuedOrder();

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('wire:loading.attr="disabled"', false);
    }

    public function test_badge_contato_manual_aparece_para_pedido_com_5_dias_ou_mais(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        $order = Order::factory()->create(['number' => 'PED-CINCODIA', 'status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(5)]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get('/painel/recuperacao/');

        $response->assertSee('Contato manual');
    }

    public function test_badge_contato_manual_nao_aparece_para_pedido_com_menos_de_5_dias(): void
    {
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        $order = Order::factory()->create(['number' => 'PED-QUATRODIA', 'status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id, 'paid_at' => now()->subDays(4)]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->get('/painel/recuperacao/');

        $response->assertDontSee('Contato manual');
    }
}
