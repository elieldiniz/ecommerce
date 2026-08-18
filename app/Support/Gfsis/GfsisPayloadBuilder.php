<?php

namespace App\Support\Gfsis;

use App\Models\IssuanceData;
use App\Models\OrderItem;
use App\Models\OrderItemGfsis;
use App\Models\ProductVariant;
use App\Models\Setting;

/**
 * Monta o payload de `POST {urlGFSIS}/gestaofacil/rest/CriaPedidoVendaLTS`
 * (RF-07) a partir de `OrderItem`/`IssuanceData`/`ProductVariant`.
 *
 * `pedido.id` é sempre lido de `order_item_gfsis.gfsis_order_id` já gravado,
 * nunca gerado aqui (RF-09). `cliente.dataNascimento` só é incluído quando
 * `issuance_data.birth_date` estiver preenchido — nunca bloqueia a montagem
 * do payload quando ausente (RF-07).
 */
final class GfsisPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(OrderItemGfsis $orderItemGfsis): array
    {
        $orderItem = $orderItemGfsis->orderItem;

        return [
            'pedido' => $this->buildPedido($orderItemGfsis),
            'cliente' => $this->buildCliente($orderItem->issuanceData),
            'certificado' => $this->buildCertificado($orderItem->productVariant, $orderItem),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPedido(OrderItemGfsis $orderItemGfsis): array
    {
        return [
            'id' => $orderItemGfsis->gfsis_order_id,
            'data' => now()->toDateString(),
            'pontoAtendimento' => (int) Setting::query()->where('key', 'gfsis_ponto_atendimento')->value('value'),
            'tipoValidacao' => (int) Setting::query()->where('key', 'gfsis_tipo_validacao')->value('value'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCliente(IssuanceData $issuanceData): array
    {
        $cliente = [
            'nome' => $issuanceData->holder_name,
            strlen($issuanceData->document) === 11 ? 'cpf' : 'cnpj' => $issuanceData->document,
            'email' => $issuanceData->email,
            'telefone' => $issuanceData->phone,
            'logradouro' => $issuanceData->street,
            'numero' => $issuanceData->number,
            'complemento' => $issuanceData->complement,
            'bairro' => $issuanceData->neighborhood,
            'municipio' => $issuanceData->city,
            'uf' => $issuanceData->state,
            'cep' => $issuanceData->postal_code,
        ];

        if ($issuanceData->ibge_code !== null) {
            $cliente['codigoIbge'] = $issuanceData->ibge_code;
        }

        if ($issuanceData->birth_date !== null) {
            $cliente['dataNascimento'] = $issuanceData->birth_date->format('Y-m-d');
        }

        return $cliente;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCertificado(ProductVariant $productVariant, OrderItem $orderItem): array
    {
        return [
            'id' => $productVariant->gfsis_certificado_id,
            'valor' => number_format((float) $orderItem->unit_price, 2, '.', ''),
        ];
    }
}
