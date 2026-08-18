<?php

namespace App\Exceptions\Refunds;

use App\Models\Payment;

/**
 * Lançada quando um estorno ativo (Pix ou Cartão) é solicitado para um
 * `Payment` que não está `authorized` (RF-12, RF-32) — bloqueia antes de
 * qualquer criação de linha em `refunds` e antes de qualquer chamada à
 * Safe2Pay.
 */
class RefundNotAllowedException extends \RuntimeException
{
    public function __construct(Payment $payment)
    {
        parent::__construct("Estorno não permitido para o pagamento [{$payment->id}]: status atual [{$payment->status->slug}], exige [authorized].");
    }
}
