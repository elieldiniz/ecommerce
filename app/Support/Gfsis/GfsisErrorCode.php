<?php

namespace App\Support\Gfsis;

/**
 * Mapeamento centralizado dos 6 códigos de erro de `CriaPedidoVendaLTS`
 * (Seção 12.2 do documento de referência) para uma mensagem legível.
 *
 * Único ponto do código-fonte que compara esses códigos GFSIS (RF-14).
 */
enum GfsisErrorCode: string
{
    case CampoFaltante = '001';
    case PedidoDuplicado = '002';
    case CepInvalido = '003';
    case CertificadoInvalido = '005';
    case DocumentoInvalido = '006';
    case ErroInesperado = '999';

    /**
     * Retorna `null` (sem lançar) quando `$code` não corresponde a nenhum
     * dos 6 códigos mapeados.
     */
    public static function fromCode(?string $code): ?self
    {
        return self::tryFrom((string) $code);
    }

    /**
     * Código `002` (pedido.id duplicado) é tratado como sucesso — idempotência,
     * sem reenvio nem incremento de `last_error` (RF-14).
     */
    public function isDuplicateSuccess(): bool
    {
        return $this === self::PedidoDuplicado;
    }

    /**
     * Mensagem legível que identifica a causa sem exigir acesso a logs brutos
     * (usada em `order_item_gfsis.last_error`, RF-14).
     */
    public function message(): string
    {
        return match ($this) {
            self::CampoFaltante => 'Campo obrigatório ausente no envio ao GFSIS.',
            self::PedidoDuplicado => 'Pedido já registrado no GFSIS (duplicado).',
            self::CepInvalido => 'CEP informado é inválido.',
            self::CertificadoInvalido => 'Certificado ou plano inválido no GFSIS.',
            self::DocumentoInvalido => 'CPF/CNPJ informado é inválido.',
            self::ErroInesperado => 'Erro inesperado retornado pelo GFSIS.',
        };
    }
}
