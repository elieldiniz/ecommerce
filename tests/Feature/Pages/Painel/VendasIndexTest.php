<?php

namespace Tests\Feature\Pages\Painel;

use App\Actions\Gfsis\GenerateIssuanceAccessToken;
use App\Jobs\RegisterOrderItemWithGfsisJob;
use App\Mail\IssuanceAccessLinkMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class VendasIndexTest extends TestCase
{
    use RefreshDatabase;

    private function paymentStatus(string $slug): PaymentStatus
    {
        return PaymentStatus::where('slug', $slug)->first()
            ?? PaymentStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    private function fulfillmentStatus(string $slug): OrderFulfillmentStatus
    {
        return OrderFulfillmentStatus::where('slug', $slug)->first()
            ?? OrderFulfillmentStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    private function paymentMethod(string $slug): PaymentMethod
    {
        return PaymentMethod::where('slug', $slug)->first()
            ?? PaymentMethod::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    public function test_route_requires_authentication(): void
    {
        $response = $this->get('/painel/vendas/');

        $response->assertRedirect(route('login'));
    }

    public function test_route_renders_the_vendas_index_view(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertOk();
    }

    public function test_lista_exibe_numeros_reais_e_nenhum_numero_fixo_do_mockup(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->create(['number' => 'PED-000101']);
        Order::factory()->create(['number' => 'PED-000102']);

        $response = $this->get('/painel/vendas/');

        $response->assertOk();
        $response->assertSee('PED-000101');
        $response->assertSee('PED-000102');
        $response->assertDontSee('#1042');
        $response->assertDontSee('#1041');
        $response->assertDontSee('#1040');
    }

    public function test_coluna_emissao_exibe_variant_erro_quando_fulfillment_status_send_failed(): void
    {
        $this->actingAs(User::factory()->create());

        $sendFailed = OrderFulfillmentStatus::where('slug', 'send_failed')->first()
            ?? OrderFulfillmentStatus::factory()->create(['slug' => 'send_failed', 'name' => 'Falha no envio']);

        Order::factory()->create(['fulfillment_status_id' => $sendFailed->id]);

        $response = $this->get('/painel/vendas/');

        $response->assertOk();
        $response->assertSee('bg-[#fbe9e9]', false);
    }

    public function test_lista_paginada_em_vinte_e_cinco_com_rodape_real(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->count(30)->create();

        $response = $this->get('/painel/vendas/');

        $response->assertOk();
        $response->assertSee('Mostrando 1 a 25 de 30');
    }

    public function test_numero_de_queries_permanece_constante_independente_da_quantidade_de_pedidos(): void
    {
        $userFor2 = User::factory()->create();
        Order::factory()->count(2)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($userFor2)->get('/painel/vendas/')->assertOk();

        $countFor2 = count(DB::getQueryLog());

        $userFor20 = User::factory()->create();
        Order::factory()->count(18)->create();

        DB::flushQueryLog();

        $this->actingAs($userFor20)->get('/painel/vendas/')->assertOk();

        $countFor20 = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame($countFor2, $countFor20);
    }

    public function test_block_filtros_renders_seven_controls(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('Período');
        $response->assertSee('Status do pagamento');
        $response->assertSee('Status da emissão');
        $response->assertSee('Forma de pagamento');
        $response->assertSee('Produto');
        $response->assertSee('Origem');
        $response->assertSee('Buscar por nome, documento ou número do pedido');
    }

    public function test_table_renders_seven_columns(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSeeInOrder(['Pedido', 'Cliente', 'Produto', 'Valor', 'Pagamento', 'Emissão', 'Data']);
    }

    public function test_block_acoes_em_lote_renders_three_buttons_com_acoes_reais(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('Exportar CSV');
        $response->assertSee('Reenviar ao GFSIS');
        $response->assertSee('Disparar recuperação');

        $content = $response->getContent();
        $mainContent = substr($content, strpos($content, 'min-w-0 flex-1'));
        $this->assertSame(0, substr_count($mainContent, 'method="POST"'), 'A área de conteúdo não deve ter formulário embutido (o form de logout da sidebar é esperado).');
    }

    public function test_tabela_de_pedidos_nao_tem_checkbox_de_selecao(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->count(2)->create();

        $response = $this->get('/painel/vendas/');

        $response->assertDontSee('<input type="checkbox"', false);
    }

    public function test_disparar_recuperacao_tem_wire_click_e_envia_email_para_fila_elegivel(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());

        $paid = $this->orderStatus('paid');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');
        $dataComplete = $this->fulfillmentStatus('data_complete');

        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $awaitingData->id]);
            OrderItem::factory()->create(['order_id' => $order->id]);
            (new GenerateIssuanceAccessToken)->execute($order->fresh(['items', 'customer']));
        }

        $foraDoCriterio = Order::factory()->create(['status_id' => $paid->id, 'fulfillment_status_id' => $dataComplete->id]);
        OrderItem::factory()->create(['order_id' => $foraDoCriterio->id]);

        Livewire::test('pages::painel.vendas')
            ->call('dispararRecuperacao')
            ->assertOk();

        Mail::assertSent(IssuanceAccessLinkMail::class, 3);
    }

    public function test_botao_disparar_recuperacao_tem_wire_click(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('wire:click="dispararRecuperacao"', false);
    }

    private function orderStatus(string $slug): OrderStatus
    {
        return OrderStatus::where('slug', $slug)->first()
            ?? OrderStatus::factory()->create(['slug' => $slug, 'name' => ucfirst($slug)]);
    }

    public function test_alterar_filtro_reseta_paginacao_para_pagina_um(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->count(30)->create();

        Livewire::test('pages::painel.vendas')
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('periodo', '30d')
            ->assertSet('paginators.page', 1);
    }

    public function test_select_status_pagamento_lista_opcoes_reais_com_todos_selecionado(): void
    {
        $this->actingAs(User::factory()->create());

        $status = $this->paymentStatus('authorized');

        $response = $this->get('/painel/vendas/');

        $response->assertSee($status->name);
    }

    public function test_select_status_emissao_lista_opcoes_reais(): void
    {
        $this->actingAs(User::factory()->create());

        $status = $this->fulfillmentStatus('sent_to_gfsis');

        $response = $this->get('/painel/vendas/');

        $response->assertSee($status->name);
    }

    public function test_select_forma_pagamento_lista_opcoes_reais(): void
    {
        $this->actingAs(User::factory()->create());

        $method = $this->paymentMethod('pix');

        $response = $this->get('/painel/vendas/');

        $response->assertSee($method->name);
    }

    public function test_select_produto_lista_opcoes_reais_ordenadas_alfabeticamente(): void
    {
        $this->actingAs(User::factory()->create());

        Product::factory()->create(['name' => 'Zebra Produto']);
        Product::factory()->create(['name' => 'Alfa Produto']);

        $response = $this->get('/painel/vendas/');

        $response->assertSeeInOrder(['Alfa Produto', 'Zebra Produto']);
    }

    public function test_filtro_status_pagamento_restringe_por_pagamento_mais_recente(): void
    {
        $this->actingAs(User::factory()->create());

        $authorized = $this->paymentStatus('authorized');
        $pending = $this->paymentStatus('pending');

        $orderAuthorized = Order::factory()->create(['number' => 'PED-AUTH01']);
        Payment::factory()->create(['order_id' => $orderAuthorized->id, 'status_id' => $authorized->id]);

        $orderPending = Order::factory()->create(['number' => 'PED-PEND01']);
        Payment::factory()->create(['order_id' => $orderPending->id, 'status_id' => $pending->id]);

        Livewire::test('pages::painel.vendas')
            ->set('statusPagamento', (string) $authorized->id)
            ->assertSee('PED-AUTH01')
            ->assertDontSee('PED-PEND01');
    }

    public function test_filtro_status_emissao_restringe_por_fulfillment_status(): void
    {
        $this->actingAs(User::factory()->create());

        $sentToGfsis = $this->fulfillmentStatus('sent_to_gfsis');
        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        Order::factory()->create(['number' => 'PED-EMIT01', 'fulfillment_status_id' => $sentToGfsis->id]);
        Order::factory()->create(['number' => 'PED-AGUA01', 'fulfillment_status_id' => $awaitingData->id]);

        Livewire::test('pages::painel.vendas')
            ->set('statusEmissao', (string) $sentToGfsis->id)
            ->assertSee('PED-EMIT01')
            ->assertDontSee('PED-AGUA01');
    }

    public function test_filtro_forma_pagamento_restringe_por_payment_method(): void
    {
        $this->actingAs(User::factory()->create());

        $pix = $this->paymentMethod('pix');
        $boleto = $this->paymentMethod('boleto');

        Order::factory()->create(['number' => 'PED-PIX001', 'payment_method_id' => $pix->id]);
        Order::factory()->create(['number' => 'PED-BOL001', 'payment_method_id' => $boleto->id]);

        Livewire::test('pages::painel.vendas')
            ->set('formaPagamento', (string) $pix->id)
            ->assertSee('PED-PIX001')
            ->assertDontSee('PED-BOL001');
    }

    public function test_filtro_produto_restringe_por_item_do_pedido(): void
    {
        $this->actingAs(User::factory()->create());

        $productA = Product::factory()->create(['name' => 'e-CPF']);
        $variantA = ProductVariant::factory()->create(['product_id' => $productA->id]);
        $productB = Product::factory()->create(['name' => 'e-CNPJ']);
        $variantB = ProductVariant::factory()->create(['product_id' => $productB->id]);

        $orderA = Order::factory()->create(['number' => 'PED-PRODA1']);
        OrderItem::factory()->create(['order_id' => $orderA->id, 'product_variant_id' => $variantA->id]);

        $orderB = Order::factory()->create(['number' => 'PED-PRODB1']);
        OrderItem::factory()->create(['order_id' => $orderB->id, 'product_variant_id' => $variantB->id]);

        Livewire::test('pages::painel.vendas')
            ->set('produto', $productA->id)
            ->assertSee('PED-PRODA1')
            ->assertDontSee('PED-PRODB1');
    }

    public function test_busca_filtra_por_nome_documento_ou_numero_do_pedido(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create(['legal_name' => 'Maria Aparecida Souza']);
        Order::factory()->create(['number' => 'PED-MARIA01', 'customer_id' => $customer->id]);
        Order::factory()->create(['number' => 'PED-OUTRO01']);

        Livewire::test('pages::painel.vendas')
            ->set('busca', 'Maria')
            ->assertSee('PED-MARIA01')
            ->assertDontSee('PED-OUTRO01');
    }

    public function test_filtro_periodo_restringe_por_created_at(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->create(['number' => 'PED-RECENTE', 'created_at' => now()->subDays(2)]);
        Order::factory()->create(['number' => 'PED-ANTIGO0', 'created_at' => now()->subDays(60)]);

        Livewire::test('pages::painel.vendas')
            ->set('periodo', '7d')
            ->assertSee('PED-RECENTE')
            ->assertDontSee('PED-ANTIGO0');
    }

    public function test_filtros_combinados_aplicam_and(): void
    {
        $this->actingAs(User::factory()->create());

        $pix = $this->paymentMethod('pix');
        $boleto = $this->paymentMethod('boleto');
        $sentToGfsis = $this->fulfillmentStatus('sent_to_gfsis');

        $awaitingData = $this->fulfillmentStatus('awaiting_data');

        Order::factory()->create(['number' => 'PED-MATCH01', 'payment_method_id' => $pix->id, 'fulfillment_status_id' => $sentToGfsis->id]);
        Order::factory()->create(['number' => 'PED-SOPIX01', 'payment_method_id' => $pix->id, 'fulfillment_status_id' => $awaitingData->id]);
        Order::factory()->create(['number' => 'PED-SOEMIT1', 'payment_method_id' => $boleto->id, 'fulfillment_status_id' => $sentToGfsis->id]);

        Livewire::test('pages::painel.vendas')
            ->set('formaPagamento', (string) $pix->id)
            ->set('statusEmissao', (string) $sentToGfsis->id)
            ->assertSee('PED-MATCH01')
            ->assertDontSee('PED-SOPIX01')
            ->assertDontSee('PED-SOEMIT1');
    }

    public function test_campo_busca_usa_debounce_300ms(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('wire:model.live.debounce.300ms="busca"', false);
    }

    public function test_numero_de_queries_com_filtros_ativos_igual_ao_baseline(): void
    {
        $pix = $this->paymentMethod('pix');

        $this->actingAs(User::factory()->create());
        Order::factory()->count(2)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        Livewire::test('pages::painel.vendas')->assertOk();

        $baselineCount = count(DB::getQueryLog());

        Order::factory()->count(2)->create(['payment_method_id' => $pix->id]);

        DB::flushQueryLog();

        Livewire::test('pages::painel.vendas', ['formaPagamento' => (string) $pix->id])
            ->assertOk();

        $filteredCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($baselineCount, $filteredCount);
    }

    public function test_rodape_reflete_total_apos_filtro_aplicado(): void
    {
        $this->actingAs(User::factory()->create());

        $pix = $this->paymentMethod('pix');
        $boleto = $this->paymentMethod('boleto');

        Order::factory()->count(12)->create(['payment_method_id' => $pix->id]);
        Order::factory()->count(18)->create(['payment_method_id' => $boleto->id]);

        $response = $this->get('/painel/vendas/');
        $response->assertSee('Mostrando 1 a 25 de 30');

        Livewire::test('pages::painel.vendas')
            ->set('formaPagamento', (string) $pix->id)
            ->assertSee('Mostrando 1 a 12 de 12');
    }

    public function test_exportar_csv_gera_arquivo_com_cabecalho_e_uma_linha_por_pedido_filtrado(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->count(30)->create(['number' => fn () => 'PED-'.fake()->unique()->numerify('######')]);

        $response = Livewire::test('pages::painel.vendas')->call('exportarCsv');

        $response->assertFileDownloaded();

        $content = base64_decode(data_get($response->effects, 'download.content'));
        $lines = array_values(array_filter(explode("\n", $content)));

        $this->assertSame('Pedido,Cliente,Produto,Valor,"Status pagamento","Status emissão",Data', $lines[0]);
        $this->assertCount(31, $lines);
    }

    public function test_exportar_csv_respeita_filtros_ativos(): void
    {
        $this->actingAs(User::factory()->create());

        $pix = $this->paymentMethod('pix');
        $boleto = $this->paymentMethod('boleto');

        Order::factory()->create(['number' => 'PED-CSVPIX0', 'payment_method_id' => $pix->id]);
        Order::factory()->create(['number' => 'PED-CSVBOL0', 'payment_method_id' => $boleto->id]);

        $response = Livewire::test('pages::painel.vendas')
            ->set('formaPagamento', (string) $pix->id)
            ->call('exportarCsv');

        $content = base64_decode(data_get($response->effects, 'download.content'));

        $this->assertStringContainsString('PED-CSVPIX0', $content);
        $this->assertStringNotContainsString('PED-CSVBOL0', $content);
    }

    public function test_botao_exportar_csv_tem_wire_click_e_wire_loading(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('wire:click="exportarCsv"', false);
        $response->assertSee('wire:target="exportarCsv"', false);
    }

    public function test_exportar_csv_exibe_toast_com_contagem(): void
    {
        $this->actingAs(User::factory()->create());

        Order::factory()->count(3)->create();

        $response = Livewire::test('pages::painel.vendas')->call('exportarCsv');

        $response->assertDispatched('toast-show');
    }

    public function test_reenviar_gfsis_despacha_job_uma_vez_por_pedido_filtrado_alem_da_paginacao(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        Order::factory()->count(30)->create();

        Livewire::test('pages::painel.vendas')
            ->call('reenviarGfsis')
            ->assertOk();

        Queue::assertPushed(RegisterOrderItemWithGfsisJob::class, 30);
    }

    public function test_reenviar_gfsis_despacha_mesmo_para_pedido_inelegivel_ao_job(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $awaitingData = $this->fulfillmentStatus('awaiting_data');
        Order::factory()->create(['fulfillment_status_id' => $awaitingData->id]);

        Livewire::test('pages::painel.vendas')
            ->call('reenviarGfsis')
            ->assertOk();

        Queue::assertPushed(RegisterOrderItemWithGfsisJob::class, 1);
    }

    public function test_reenviar_gfsis_respeita_filtros_ativos(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $pix = $this->paymentMethod('pix');
        $boleto = $this->paymentMethod('boleto');

        Order::factory()->create(['payment_method_id' => $pix->id]);
        Order::factory()->create(['payment_method_id' => $boleto->id]);

        Livewire::test('pages::painel.vendas')
            ->set('formaPagamento', (string) $pix->id)
            ->call('reenviarGfsis');

        Queue::assertPushed(RegisterOrderItemWithGfsisJob::class, 1);
    }

    public function test_botao_reenviar_gfsis_tem_wire_click_e_wire_loading(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('wire:click="reenviarGfsis"', false);
        $response->assertSee('wire:target="reenviarGfsis"', false);
    }

    public function test_reenviar_gfsis_exibe_toast_com_contagem(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        Order::factory()->count(2)->create();

        $response = Livewire::test('pages::painel.vendas')->call('reenviarGfsis');

        $response->assertDispatched('toast-show');
    }
}
