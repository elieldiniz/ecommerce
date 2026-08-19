<?php

namespace Tests\Feature\Pages\Painel;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\GfsisStatus;
use App\Models\HolderType;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientesShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_requires_authentication(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertRedirect(route('login'));
    }

    public function test_id_inexistente_retorna_404(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/painel/clientes/999999/');

        $response->assertNotFound();
    }

    public function test_bloco_ficha_exibe_dados_reais_do_cliente(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);
        $customer = Customer::factory()->create([
            'legal_name' => 'Maria Teste',
            'document' => '12345678900',
            'email' => 'maria.teste@exemplo.com.br',
            'phone' => '11999998888',
            'holder_type_id' => $pf->id,
        ]);
        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_primary' => true,
            'street' => 'Rua Teste',
            'state' => 'SP',
        ]);

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();
        $response->assertSee('Maria Teste');
        $response->assertSee('Pessoa Física');
        $response->assertSee('12345678900');
        $response->assertSee('maria.teste@exemplo.com.br');
        $response->assertSee('Rua Teste');
        $response->assertSee('SP');
    }

    public function test_cliente_sem_endereco_primario_renderiza_placeholder_sem_erro(): void
    {
        $this->actingAs(User::factory()->create());

        $pf = HolderType::factory()->create(['slug' => 'pf', 'name' => 'Pessoa Física']);
        $customer = Customer::factory()->create(['holder_type_id' => $pf->id]);

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();

        $html = $response->getContent();
        $start = strpos($html, 'Ficha do cliente · dados</h2>');
        $end = strpos($html, '</section>', $start);
        $fichaBlock = substr($html, $start, $end - $start);
        $this->assertSame(7, substr_count($fichaBlock, '—'));
    }

    public function test_bloco_historico_exibe_pedidos_reais(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['number' => 'PED-000201', 'customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'name_snapshot' => 'e-CPF A1 · 12 meses']);

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();
        $response->assertSee('PED-000201');
        $response->assertSee('e-CPF A1 · 12 meses');
        $response->assertSee(route('painel.vendas.show', $order->id), false);
    }

    public function test_historico_vazio_exibe_mensagem(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();
        $response->assertSee('Nenhum pedido encontrado');
    }

    public function test_bloco_titulares_exibe_titulares_reais(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        IssuanceData::factory()->create([
            'order_item_id' => $item->id,
            'holder_name' => 'Titular Teste',
            'document' => '98765432100',
        ]);

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();
        $response->assertSee('Titular Teste');
        $response->assertSee('98765432100');
    }

    public function test_titulares_vazio_exibe_mensagem(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();
        $response->assertSee('Nenhum titular encontrado');
    }

    public function test_link_voltar_presente_e_aponta_para_lista(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();
        $response->assertSee('Voltar');
        $response->assertSee(route('painel.clientes'), false);
    }

    public function test_bloco_titulares_exibe_certificado_ate_do_order_item_gfsis(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        $issuanceData = IssuanceData::factory()->create(['order_item_id' => $item->id]);
        $status = GfsisStatus::factory()->create();
        OrderItemGfsis::factory()->create([
            'order_item_id' => $item->id,
            'status_id' => $status->id,
            'certificate_expires_at' => '2027-08-24',
        ]);

        $response = $this->get("/painel/clientes/{$customer->id}/");

        $response->assertOk();
        $response->assertSee($issuanceData->holder_name);
        $response->assertSee('24/08/2027');
    }

    public function test_numero_de_queries_permanece_constante_independente_da_quantidade_de_pedidos_e_titulares(): void
    {
        $userFor2 = User::factory()->create();
        $customerFor2 = Customer::factory()->create();
        for ($i = 0; $i < 2; $i++) {
            $order = Order::factory()->create(['customer_id' => $customerFor2->id]);
            $item = OrderItem::factory()->create(['order_id' => $order->id]);
            IssuanceData::factory()->create(['order_item_id' => $item->id]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($userFor2)->get("/painel/clientes/{$customerFor2->id}/")->assertOk();

        $countFor2 = count(DB::getQueryLog());

        $userFor20 = User::factory()->create();
        $customerFor20 = Customer::factory()->create();
        for ($i = 0; $i < 20; $i++) {
            $order = Order::factory()->create(['customer_id' => $customerFor20->id]);
            $item = OrderItem::factory()->create(['order_id' => $order->id]);
            IssuanceData::factory()->create(['order_item_id' => $item->id]);
        }

        DB::flushQueryLog();

        $this->actingAs($userFor20)->get("/painel/clientes/{$customerFor20->id}/")->assertOk();

        $countFor20 = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame($countFor2, $countFor20);
    }
}
