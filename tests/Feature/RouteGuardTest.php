<?php

namespace Tests\Feature;

use App\Models\Order;
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
     * @return array<int, array{0: string}>
     */
    public static function rotasPublicasDeLoja(): array
    {
        return [
            ['/checkout/'],
            ['/pedido/1042/pagamento/'],
            ['/pedido/1042/emissao/'],
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
