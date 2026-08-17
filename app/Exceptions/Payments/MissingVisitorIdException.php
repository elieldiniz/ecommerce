<?php

namespace App\Exceptions\Payments;

/**
 * Lançada quando a cobrança de Cartão de Crédito seria montada sem `VisitorID`
 * capturado pelo script de anti-fraude front-end (RF-31) — bloqueia antes de
 * qualquer chamada à Safe2Pay.
 */
class MissingVisitorIdException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('VisitorID é obrigatório para cobrança de Cartão de Crédito (RF-31).');
    }
}
