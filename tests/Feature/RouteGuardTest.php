<?php

namespace Tests\Feature;

use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string}>
     */
    public static function rotasDoPainel(): array
    {
        return [
            ['/painel/'],
            ['/painel/vendas/'],
            ['/painel/vendas/1042/'],
            ['/painel/recuperacao/'],
            ['/painel/produtos/'],
            ['/painel/formas-pagamento/'],
            ['/painel/clientes/'],
            ['/painel/relatorios/'],
        ];
    }

    /**
     * `pedido/{id}/emissao/` não entra aqui: desde RF-17, a rota exige
     * `?token=` correspondente a um `issuance_data.access_token` válido,
     * respondendo 403 sem ele (fecha o IDOR) — testada separadamente abaixo.
     *
     * @return array<int, array{0: string}>
     */
    public static function rotasPublicasDeLoja(): array
    {
        return [
            ['/checkout/'],
            ['/pedido/1042/pagamento/'],
            ['/minha-conta/pedidos/'],
        ];
    }

    #[DataProvider('rotasDoPainel')]
    public function test_guest_is_redirected_from_all_eight_painel_routes(string $rota): void
    {
        $response = $this->get($rota);

        $response->assertRedirect(route('login'));
        $response->assertStatus(302);
    }

    #[DataProvider('rotasPublicasDeLoja')]
    public function test_guest_gets_200_from_all_four_public_customer_routes(string $rota): void
    {
        $response = $this->get($rota);

        $response->assertOk();
        $response->assertStatus(200);
    }

    #[DataProvider('rotasDoPainel')]
    public function test_authenticated_staff_gets_200_from_all_eight_painel_routes(string $rota): void
    {
        Order::factory()->create(['id' => 1042]);

        $this->actingAs(User::factory()->create());

        $response = $this->get($rota);

        $response->assertOk();
    }

    public function test_guest_is_forbidden_from_pedido_emissao_route_without_token(): void
    {
        $response = $this->get('/pedido/1042/emissao/');

        $response->assertForbidden();
    }

    public function test_guest_gets_200_from_pedido_emissao_route_with_a_valid_token(): void
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);
        $issuanceData = IssuanceData::factory()->create([
            'order_item_id' => $orderItem->id,
            'access_token_expires_at' => now()->addDay(),
        ]);

        $response = $this->get('/pedido/'.$order->id.'/emissao/?token='.$issuanceData->access_token);

        $response->assertOk();
    }

    public function test_guest_post_to_safe2pay_webhook_is_not_redirected_to_login(): void
    {
        $response = $this->post('/webhooks/safe2pay', []);

        $this->assertNotSame(302, $response->getStatusCode());
    }

    public function test_post_to_safe2pay_webhook_without_csrf_token_does_not_return_419(): void
    {
        $response = $this->post('/webhooks/safe2pay', []);

        $this->assertNotSame(419, $response->getStatusCode());
    }

    public function test_guest_post_to_gfsis_webhook_is_not_redirected_to_login(): void
    {
        $response = $this->post('/webhooks/gfsis', []);

        $this->assertNotSame(302, $response->getStatusCode());
    }

    public function test_post_to_gfsis_webhook_without_csrf_token_does_not_return_419(): void
    {
        $response = $this->post('/webhooks/gfsis', []);

        $this->assertNotSame(419, $response->getStatusCode());
    }
}
