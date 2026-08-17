<?php

namespace App\Exceptions\Payments;

/**
 * Lançada quando o número de parcelas solicitado excede `payment_methods.max_installments`
 * da forma `cartao` (RF-14) — bloqueia antes de qualquer chamada à Safe2Pay.
 */
class InstallmentLimitExceededException extends \RuntimeException
{
    public function __construct(int $requested, int $maxInstallments)
    {
        parent::__construct("Parcelamento solicitado [{$requested}] excede o máximo permitido [{$maxInstallments}] para cartão de crédito.");
    }
}
