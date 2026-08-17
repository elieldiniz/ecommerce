<?php

namespace App\Exceptions\Payments;

/**
 * Lançada quando o total recalculado no backend diverge do total apresentado
 * pelo front-end no momento da submissão (RF-28) — bloqueia o envio à Safe2Pay
 * antes de qualquer chamada HTTP e sem criar linha em `payments`.
 */
class PaymentTotalMismatchException extends \RuntimeException
{
    public function __construct(string $recalculatedTotal, string $frontTotal)
    {
        parent::__construct("Total divergente: recalculado [{$recalculatedTotal}], recebido do front [{$frontTotal}].");
    }
}
