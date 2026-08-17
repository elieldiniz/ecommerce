<?php

namespace Tests\Unit\Support\Safe2Pay;

use App\Support\Safe2Pay\Safe2PayClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Safe2PayClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.safe2pay.base_url' => 'https://payment.safe2pay.com.br',
            'services.safe2pay.api_key_sandbox' => 'sandbox-key',
            'services.safe2pay.api_key_production' => 'production-key',
        ]);
    }

    public function test_charge_posts_to_v2_payment_with_sandbox_api_key_and_is_sandbox_from_config(): void
    {
        config(['services.safe2pay.is_sandbox' => true]);
        Http::fake(['*' => Http::response(['IdTransaction' => 1], 200)]);

        (new Safe2PayClient)->charge(['PaymentMethod' => '6']);

        Http::assertSent(fn ($request) => $request->url() === 'https://payment.safe2pay.com.br/v2/payment'
            && $request->method() === 'POST'
            && $request->hasHeader('X-API-KEY', 'sandbox-key')
            && $request['PaymentMethod'] === '6'
            && $request['IsSandbox'] === true);
    }

    public function test_charge_uses_production_api_key_and_is_sandbox_false_when_configured(): void
    {
        config(['services.safe2pay.is_sandbox' => false]);
        Http::fake(['*' => Http::response(['IdTransaction' => 1], 200)]);

        (new Safe2PayClient)->charge(['PaymentMethod' => '2']);

        Http::assertSent(fn ($request) => $request->hasHeader('X-API-KEY', 'production-key')
            && $request['IsSandbox'] === false);
    }

    public function test_charge_ignores_any_is_sandbox_value_passed_in_the_payload(): void
    {
        config(['services.safe2pay.is_sandbox' => true]);
        Http::fake(['*' => Http::response([], 200)]);

        (new Safe2PayClient)->charge(['IsSandbox' => false]);

        Http::assertSent(fn ($request) => $request['IsSandbox'] === true);
    }

    public function test_query_sends_a_get_request_to_the_transaction_status_endpoint(): void
    {
        config(['services.safe2pay.is_sandbox' => true]);
        Http::fake(['*' => Http::response([], 200)]);

        (new Safe2PayClient)->query('12345');

        Http::assertSent(fn ($request) => $request->url() === 'https://payment.safe2pay.com.br/v2/payment/12345'
            && $request->method() === 'GET'
            && $request->hasHeader('X-API-KEY', 'sandbox-key'));
    }

    public function test_refund_pix_sends_a_delete_request_to_the_pix_refund_endpoint(): void
    {
        config(['services.safe2pay.is_sandbox' => true]);
        Http::fake(['*' => Http::response([], 200)]);

        (new Safe2PayClient)->refundPix('12345', ['reason' => 'test']);

        Http::assertSent(fn ($request) => $request->url() === 'https://payment.safe2pay.com.br/v2/payment/12345/cobranca_pix-estornar'
            && $request->method() === 'DELETE'
            && $request->hasHeader('X-API-KEY', 'sandbox-key')
            && $request['reason'] === 'test');
    }

    public function test_refund_card_sends_a_delete_request_to_the_card_refund_endpoint(): void
    {
        config(['services.safe2pay.is_sandbox' => true]);
        Http::fake(['*' => Http::response([], 200)]);

        (new Safe2PayClient)->refundCard('12345', ['reason' => 'test']);

        Http::assertSent(fn ($request) => $request->url() === 'https://payment.safe2pay.com.br/v2/payment/12345/estornar'
            && $request->method() === 'DELETE'
            && $request->hasHeader('X-API-KEY', 'sandbox-key')
            && $request['reason'] === 'test');
    }
}
