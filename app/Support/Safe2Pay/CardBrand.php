<?php

namespace App\Support\Safe2Pay;

/**
 * Mapeamento das bandeiras de cartão suportadas pela Safe2Pay (tabela
 * "Bandeiras suportadas" da documentação de referência) para o rótulo
 * exibido em `payments.card_brand`.
 */
enum CardBrand: int
{
    case Visa = 1;
    case MasterCard = 2;
    case AmericanExpress = 3;
    case Elo = 7;
    case Aura = 8;
    case JCB = 9;
    case DinersClub = 10;
    case Discover = 11;

    public function label(): string
    {
        return match ($this) {
            self::Visa => 'Visa',
            self::MasterCard => 'Mastercard',
            self::AmericanExpress => 'American Express',
            self::Elo => 'Elo',
            self::Aura => 'Aura',
            self::JCB => 'JCB',
            self::DinersClub => 'Diners Club',
            self::Discover => 'Discover',
        };
    }
}
