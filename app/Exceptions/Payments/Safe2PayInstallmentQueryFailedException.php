<?php

namespace App\Exceptions\Payments;

/**
 * Lançada quando `GET /v2/creditCard/installmentValue` falha — timeout/erro de
 * rede, `$response->failed()`, `HasError: true` no corpo, ou resposta sem
 * `ResponseDetail.Installments[]`. Diferente de `Safe2PayTokenizationFailedException`/
 * `Safe2PayChargeFailedException`, esta exceção é lançada e capturada inteiramente
 * server-side dentro de `FetchInstallmentValues`/`ChargeCardPayment`/`⚡checkout.blade.php`
 * (RF-05) — nunca chega a um handler de exceção HTTP, por isso não define `render()`.
 */
class Safe2PayInstallmentQueryFailedException extends \RuntimeException
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(private readonly array $rawResponse = [])
    {
        parent::__construct('A Safe2Pay não retornou valores de parcelamento válidos.');
    }

    /**
     * @return array<string, mixed>
     */
    public function rawResponse(): array
    {
        return $this->rawResponse;
    }
}
