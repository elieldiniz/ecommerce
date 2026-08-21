<?php

namespace App\Exceptions\Payments;

use Illuminate\Http\JsonResponse;

/**
 * Lançada quando `POST /v2/token` falha — seja porque a Safe2Pay respondeu
 * HTTP 200 com `HasError: true` no corpo (a gateway recusou a tokenização),
 * seja porque a chamada HTTP em si falhou (rede/erro HTTP) — em ambos os
 * casos nenhuma chave `token` chega à resposta do cliente (RF-02, CT-02).
 * Carrega o corpo bruto da resposta/erro para quem a captura poder
 * investigar, mas nunca a expõe ao cliente como está (RF-03) — `render()`
 * devolve apenas uma mensagem genérica.
 */
class Safe2PayTokenizationFailedException extends \RuntimeException
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(private readonly array $rawResponse)
    {
        parent::__construct('A Safe2Pay recusou a tokenização do cartão.');
    }

    /**
     * @return array<string, mixed>
     */
    public function rawResponse(): array
    {
        return $this->rawResponse;
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => 'Não foi possível tokenizar o cartão. Tente novamente.'], 422);
    }
}
