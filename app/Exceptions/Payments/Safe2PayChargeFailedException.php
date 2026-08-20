<?php

namespace App\Exceptions\Payments;

/**
 * Lançada quando a Safe2Pay responde HTTP 200 a `POST /v2/payment` com
 * `HasError: true` no corpo — a gateway recusou a cobrança sem sinalizar erro
 * via status HTTP, então `$response->throw()` nunca captura esse caso (RF-01).
 * Carrega o corpo bruto da resposta para quem a captura poder logar/investigar
 * sem consultar a Safe2Pay novamente (RF-02) — nunca exibido ao cliente como
 * está (RF-03), isso é responsabilidade de quem captura.
 */
class Safe2PayChargeFailedException extends \RuntimeException
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(private readonly array $rawResponse)
    {
        parent::__construct('A Safe2Pay recusou a cobrança (HasError: true em uma resposta HTTP 200).');
    }

    /**
     * @return array<string, mixed>
     */
    public function rawResponse(): array
    {
        return $this->rawResponse;
    }
}
