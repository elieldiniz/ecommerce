<?php

namespace App\Support\Safe2Pay;

/**
 * Mapeamento centralizado dos 13 códigos de status transacionais da Safe2Pay
 * (Seção 9 do documento de referência) para o slug interno de `payment_statuses`.
 *
 * Único ponto do código-fonte que compara esses 13 números (RF-18).
 */
enum TransactionStatus: int
{
    case Pendente = 1;
    case Processamento = 2;
    case Autorizado = 3;
    case EmDisputa = 5;
    case Estornado = 6;
    case Baixado = 7;
    case Recusado = 8;
    case Liberado = 11;
    case EmCancelamento = 12;
    case Chargeback = 13;
    case PreAutorizado = 14;
    case DevolucaoDeContestacao = 15;
    case EmDevolucao = 19;

    /**
     * @throws \ValueError quando `$code` não corresponde a nenhum dos 13 códigos mapeados.
     */
    public static function fromCode(int $code): self
    {
        return self::from($code);
    }

    /**
     * Slug de `payment_statuses` equivalente a este código Safe2Pay.
     *
     * O código 7 (Baixado) é traduzido explicitamente para `expired` — não é
     * uma cópia literal do rótulo Safe2Pay (RF-17).
     */
    public function toInternalStatusSlug(): string
    {
        return match ($this) {
            self::Pendente, self::Liberado, self::EmCancelamento => 'pending',
            self::Processamento, self::EmDisputa, self::PreAutorizado, self::EmDevolucao => 'under_review',
            self::Autorizado, self::DevolucaoDeContestacao => 'authorized',
            self::Recusado => 'denied',
            self::Estornado, self::Chargeback => 'reversed',
            self::Baixado => 'expired',
        };
    }
}
