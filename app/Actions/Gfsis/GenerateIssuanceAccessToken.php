<?php

namespace App\Actions\Gfsis;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\IssuanceData;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

/**
 * RF-16: cria (idempotente, `firstOrCreate`) a primeira linha de `issuance_data`
 * por `order_item` do pedido no exato momento em que `orders.status` transiciona
 * para `pago`, pré-preenchendo os campos `NOT NULL` a partir do `Customer`/
 * `CustomerAddress` primário (mesma fonte de `Safe2Pay\PaymentPayloadBuilder::base()`)
 * e o `holder_type_id` do produto comprado (RF-02 — o produto define PF/PJ, não
 * o cadastro do cliente). `filled_at` permanece sempre `null`: só é gravado pela
 * submissão explícita do formulário de emissão, nunca por este pré-preenchimento.
 */
class GenerateIssuanceAccessToken
{
    private const TOKEN_TTL_DAYS = 30;

    public function execute(Order $order): void
    {
        $customer = $order->customer;
        $address = $customer->addresses()->where('is_primary', true)->first();

        foreach ($order->items as $item) {
            IssuanceData::query()->firstOrCreate(
                ['order_item_id' => $item->id],
                $this->defaults($item, $customer, $address),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(OrderItem $item, Customer $customer, ?CustomerAddress $address): array
    {
        return [
            'holder_type_id' => $item->productVariant->product->holder_type_id,
            'holder_name' => $customer->legal_name,
            'document' => $customer->document,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'postal_code' => $address?->postal_code ?? '',
            'street' => $address?->street ?? '',
            'number' => $address?->number ?? '',
            'complement' => $address?->complement,
            'neighborhood' => $address?->neighborhood ?? '',
            'city' => $address?->city ?? '',
            'state' => $address?->state ?? '',
            'ibge_code' => $address?->ibge_code,
            'access_token' => $this->generateUniqueToken(),
            'access_token_expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
            'filled_at' => null,
        ];
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (IssuanceData::query()->where('access_token', $token)->exists());

        return $token;
    }
}
