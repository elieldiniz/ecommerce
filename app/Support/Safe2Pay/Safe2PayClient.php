<?php

namespace App\Support\Safe2Pay;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Único ponto de saída HTTP para a API de cobrança da Safe2Pay
 * (`https://payment.safe2pay.com.br/v2`).
 *
 * A credencial `X-API-KEY` é sempre lida de `config('services.safe2pay.*')`,
 * nunca hardcoded (RNF-02); `IsSandbox` é sempre incluído em `charge()` a
 * partir da mesma configuração, nunca fixo no código (RNF-03).
 */
final class Safe2PayClient
{
    public function charge(array $payload): Response
    {
        return $this->client()->post('/v2/payment', [
            ...$payload,
            'IsSandbox' => $this->isSandbox(),
        ]);
    }

    public function query(string $transactionId): Response
    {
        return $this->client()->get("/v2/payment/{$transactionId}");
    }

    public function refundPix(string $transactionId, array $payload = []): Response
    {
        return $this->client()->delete("/v2/payment/{$transactionId}/cobranca_pix-estornar", $payload);
    }

    public function refundCard(string $transactionId, array $payload = []): Response
    {
        return $this->client()->delete("/v2/payment/{$transactionId}/estornar", $payload);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.safe2pay.base_url'))
            ->withHeaders(['X-API-KEY' => $this->apiKey()]);
    }

    private function apiKey(): string
    {
        return (string) ($this->isSandbox()
            ? config('services.safe2pay.api_key_sandbox')
            : config('services.safe2pay.api_key_production'));
    }

    private function isSandbox(): bool
    {
        return (bool) config('services.safe2pay.is_sandbox');
    }
}
