<?php

namespace Tests\Feature\Http\Checkout;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TokenizeCardControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.safe2pay.base_url' => 'https://payment.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.api_key_production' => 'production-key',
            'services.safe2pay.is_sandbox' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'holder' => 'Maria Aparecida Souza',
            'cardNumber' => '5105105105105100',
            'expirationDate' => '12/2030',
            'securityCode' => '123',
        ];
    }

    public function test_a_successful_tokenization_returns_exactly_token_brand_and_last4(): void
    {
        Http::fake(['*' => Http::response([
            'HasError' => false,
            'ResponseDetail' => [
                'Token' => 'tok_abc123',
                'Brand' => 2,
                'CardNumber' => '510510*****5100',
            ],
        ], 200)]);

        $response = $this->postJson('/checkout/tokenizar-cartao', $this->payload());

        $response->assertOk();
        $response->assertExactJson([
            'token' => 'tok_abc123',
            'brand' => 'Mastercard',
            'last4' => '5100',
        ]);
    }

    public function test_has_error_true_blocks_any_token_in_the_response(): void
    {
        Http::fake(['*' => Http::response(['HasError' => true, 'Error' => 'Cartão recusado'], 200)]);

        $response = $this->postJson('/checkout/tokenizar-cartao', $this->payload());

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
        $response->assertJsonMissing(['token' => null]);
        $this->assertArrayNotHasKey('token', $response->json());
    }

    public function test_when_the_safe2pay_http_call_fails_the_response_has_no_token(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $response = $this->postJson('/checkout/tokenizar-cartao', $this->payload());

        $response->assertStatus(422);
        $this->assertArrayNotHasKey('token', $response->json());
    }

    public function test_a_missing_required_field_fails_validation(): void
    {
        foreach (['holder', 'cardNumber', 'expirationDate', 'securityCode'] as $field) {
            $payload = $this->payload();
            unset($payload[$field]);

            $response = $this->postJson('/checkout/tokenizar-cartao', $payload);

            $response->assertStatus(422);
            $this->assertArrayNotHasKey('token', $response->json());
        }
    }

    public function test_credentials_are_read_from_config_not_hardcoded(): void
    {
        config([
            'services.safe2pay.api_key_sandbox' => 'sandbox-key-from-config',
            'services.safe2pay.is_sandbox' => true,
        ]);
        Http::fake(['*' => Http::response(['HasError' => false, 'ResponseDetail' => [
            'Token' => 'tok_abc123', 'Brand' => 1, 'CardNumber' => '411111*****1111',
        ]], 200)]);

        $this->postJson('/checkout/tokenizar-cartao', $this->payload());

        Http::assertSent(fn ($request) => $request->url() === 'https://payment.safe2pay.com.br/v2/token'
            && $request->hasHeader('X-API-KEY', (string) config('services.safe2pay.api_key_sandbox')));
    }

    public function test_the_raw_card_payload_and_response_are_never_logged(): void
    {
        Log::spy();

        Http::fake(['*' => Http::response(['HasError' => false, 'ResponseDetail' => [
            'Token' => 'tok_abc123', 'Brand' => 1, 'CardNumber' => '411111*****1111',
        ]], 200)]);
        $this->postJson('/checkout/tokenizar-cartao', $this->payload());

        Http::fake(['*' => Http::response(['HasError' => true, 'Error' => 'Cartão recusado'], 200)]);
        $this->postJson('/checkout/tokenizar-cartao', $this->payload());

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('debug');
    }
}
