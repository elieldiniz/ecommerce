<?php

namespace Tests\Unit\Support\Gfsis;

use App\Support\Gfsis\GfsisClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GfsisClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gfsis.base_url' => 'https://gfsis.example.com',
            'services.gfsis.login' => 'integracao-login',
            'services.gfsis.senha' => 'integracao-senha',
        ]);
    }

    public function test_two_consecutive_calls_to_criar_pedido_venda_reuse_the_cached_token(): void
    {
        Http::fake([
            '*/gestaofacil/rest/auth' => Http::response([
                'acessToken' => 'token-1',
                'expirationDate' => now()->addMinutes(30)->format('Y-m-d H:i'),
            ], 200),
            '*/gestaofacil/rest/CriaPedidoVendaLTS' => Http::response([
                'erro' => false,
                'codigo' => '102930',
                'mensagem' => 'Pedido criado com sucesso!',
                'urlPagamento' => 'https://demonstracao.gfsis.com.br/pagamento/1',
            ], 201),
        ]);

        $client = new GfsisClient;

        $client->criarPedidoVenda(['pedido' => ['id' => 1]]);
        $client->criarPedidoVenda(['pedido' => ['id' => 1]]);

        Http::assertSentCount(3);

        $authRequests = Http::recorded(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/auth'));
        $this->assertCount(1, $authRequests);
    }

    public function test_a_401_response_triggers_a_new_token_before_repeating_the_original_call(): void
    {
        Http::fake([
            '*/gestaofacil/rest/auth' => Http::sequence()
                ->push(['acessToken' => 'token-1', 'expirationDate' => now()->addMinutes(30)->format('Y-m-d H:i')], 200)
                ->push(['acessToken' => 'token-2', 'expirationDate' => now()->addMinutes(30)->format('Y-m-d H:i')], 200),
            '*/gestaofacil/rest/CriaPedidoVendaLTS' => Http::sequence()
                ->push([], 401)
                ->push(['erro' => false, 'codigo' => '102930'], 201),
        ]);

        $client = new GfsisClient;

        $response = $client->criarPedidoVenda(['pedido' => ['id' => 1]]);

        $this->assertSame(201, $response->status());

        $authRequests = Http::recorded(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/auth'));
        $this->assertCount(2, $authRequests);

        $pedidoRequests = Http::recorded(
            fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS')
        )->values();
        $this->assertCount(2, $pedidoRequests);

        $this->assertTrue($pedidoRequests[0][0]->hasHeader('Authorization', 'Bearer token-1'));
        $this->assertTrue($pedidoRequests[1][0]->hasHeader('Authorization', 'Bearer token-2'));
    }

    public function test_auth_uses_basic_auth_header_and_criar_pedido_venda_uses_bearer_token_header(): void
    {
        Http::fake([
            '*/gestaofacil/rest/auth' => Http::response([
                'acessToken' => 'token-abc',
                'expirationDate' => now()->addMinutes(30)->format('Y-m-d H:i'),
            ], 200),
            '*/gestaofacil/rest/CriaPedidoVendaLTS' => Http::response(['erro' => false, 'codigo' => '1'], 201),
        ]);

        $client = new GfsisClient;
        $client->criarPedidoVenda(['pedido' => ['id' => 1]]);

        $expectedBasic = 'Basic '.base64_encode('integracao-login:integracao-senha');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/auth')
            && $request->hasHeader('Authorization', $expectedBasic));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/gestaofacil/rest/CriaPedidoVendaLTS')
            && $request->hasHeader('Authorization', 'Bearer token-abc'));
    }
}
