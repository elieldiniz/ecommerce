<?php

namespace App\Actions\Checkout;

use App\Exceptions\Payments\Safe2PayInstallmentQueryFailedException;
use App\Support\Safe2Pay\Safe2PayClient;
use Illuminate\Support\Facades\Cache;

class FetchInstallmentValues
{
    /**
     * Consulta os valores de parcelamento da Safe2Pay (CT-01) para um valor,
     * cacheando a resposta por `$amount` (RF-06) — nunca cacheia falha, para que
     * uma nova tentativa dispare uma nova consulta real (RF-05). Nenhuma fórmula
     * de juro é aplicada aqui: cada linha é lida diretamente do corpo da resposta
     * (RF-02).
     *
     * @return list<array{installments: int, installment_value: string, total_value: string, applied_tax: string}>
     *
     * @throws Safe2PayInstallmentQueryFailedException
     */
    public function execute(string $amount): array
    {
        $cacheKey = "safe2pay.installment_value.{$amount}";

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = (new Safe2PayClient)->installmentValue($amount);
        } catch (\Throwable $e) {
            throw new Safe2PayInstallmentQueryFailedException(['exception' => $e->getMessage()]);
        }

        if ($response->failed() || $response->json('HasError') === true) {
            throw new Safe2PayInstallmentQueryFailedException((array) $response->json());
        }

        $rows = $response->json('ResponseDetail.Installments');

        if (! is_array($rows) || $rows === []) {
            throw new Safe2PayInstallmentQueryFailedException((array) $response->json());
        }

        $installments = array_map(fn (array $row): array => [
            'installments' => (int) $row['Installments'],
            'installment_value' => (string) $row['InstallmentValue'],
            'total_value' => (string) $row['TotalValue'],
            'applied_tax' => (string) $row['AppliedTax'],
        ], $rows);

        Cache::put($cacheKey, $installments, now()->addHour());

        return $installments;
    }
}
