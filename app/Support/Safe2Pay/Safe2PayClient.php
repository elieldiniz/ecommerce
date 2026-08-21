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

    public function tokenize(array $payload): Response
    {
        return $this->client()->post('/v2/token', $payload);
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

    /**
     * Consulta os valores de parcelamento (CT-01) para um valor — host dedicado
     * (`installment_base_url`, confirmado por teste real contra a Safe2Pay: `base_url`
     * responde 404 para este endpoint). `$amount` é enviado como decimal, no mesmo
     * formato já usado pela aplicação (`"180.00"`) — confirmado também por teste real
     * que a Safe2Pay aceita o valor decimal diretamente, sem conversão para centavos.
     */
    public function installmentValue(string $amount): Response
    {
        return $this->installmentClient()->get('/v2/creditCard/installmentValue', ['amount' => $amount]);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.safe2pay.base_url'))
            ->withHeaders(['X-API-KEY' => $this->apiKey()]);
    }

    private function installmentClient(): PendingRequest
    {
        return Http::baseUrl((string) config('services.safe2pay.installment_base_url'))
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
