<?php

namespace App\Support\Gfsis;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Único ponto de saída HTTP para a API do GFSIS
 * (`{urlGFSIS}/gestaofacil/rest/*`).
 *
 * `auth()` cacheia o token via `Cache` respeitando `expirationDate` (CT-01),
 * nunca obtendo um novo token a cada chamada (RF-06); `criarPedidoVenda()`
 * (CT-02) reutiliza o token em cache e, em resposta `401`, invalida o cache e
 * tenta novamente uma única vez com um token recém-obtido (RF-06).
 * Credenciais e URL base sempre lidas de `config('services.gfsis.*')`, nunca
 * hardcoded (RNF-01, RNF-02).
 */
final class GfsisClient
{
    private const CACHE_KEY = 'gfsis.access_token';

    public function auth(): string
    {
        $token = Cache::get(self::CACHE_KEY);

        if (is_string($token)) {
            return $token;
        }

        return $this->refreshToken();
    }

    public function criarPedidoVenda(array $payload): Response
    {
        $response = $this->client()
            ->withToken($this->auth())
            ->post('/gestaofacil/rest/CriaPedidoVendaLTS', $payload);

        if ($response->status() === 401) {
            Cache::forget(self::CACHE_KEY);

            $response = $this->client()
                ->withToken($this->refreshToken())
                ->post('/gestaofacil/rest/CriaPedidoVendaLTS', $payload);
        }

        return $response;
    }

    private function refreshToken(): string
    {
        $response = $this->client()
            ->withBasicAuth(
                (string) config('services.gfsis.login'),
                (string) config('services.gfsis.senha'),
            )
            ->post('/gestaofacil/rest/auth');

        $token = (string) $response->json('acessToken');
        $expirationDate = Carbon::parse((string) $response->json('expirationDate'));

        Cache::put(self::CACHE_KEY, $token, $expirationDate);

        return $token;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('services.gfsis.base_url'));
    }
}
