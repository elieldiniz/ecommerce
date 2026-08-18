<?php

namespace App\Exceptions\Gfsis;

use RuntimeException;

/**
 * RF-10: lançada quando um bloqueio local (equivalente ao erro `005` do
 * GFSIS) impede a chamada a `CriaPedidoVendaLTS` antes de qualquer requisição
 * HTTP — hoje o único caso é `product_variants.gfsis_certificado_id` ausente.
 */
class GfsisRegistrationBlockedException extends RuntimeException {}
