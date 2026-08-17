<?php

namespace App\Support\Safe2Pay;

/**
 * Único ponto do código-fonte que deriva o `PaymentMethod` numérico da Safe2Pay
 * a partir de `payment_methods.slug` (RF-05).
 */
final class PaymentMethodCode
{
    public static function fromSlug(string $slug): string
    {
        return match ($slug) {
            'pix' => '6',
            'cartao' => '2',
            'boleto' => '1',
            default => throw new \InvalidArgumentException("Slug de forma de pagamento desconhecido: [{$slug}]."),
        };
    }
}
