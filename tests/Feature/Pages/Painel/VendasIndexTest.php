<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendasIndexTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_block_acoes_em_lote_renders_three_buttons_without_real_action(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/vendas/');

        $response->assertSee('Exportar CSV');
        $response->assertSee('Reenviar ao GFSIS');
        $response->assertSee('Disparar recuperação');

        $this->assertSame(0, substr_count($response->getContent(), 'method="POST"'));
    }
}
