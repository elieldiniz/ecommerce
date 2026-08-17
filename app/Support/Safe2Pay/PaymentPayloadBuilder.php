<?php

namespace App\Support\Safe2Pay;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;

/**
 * Monta o envelope comum de CT-01 (`POST /v2/payment`), reaproveitado pelas
 * três formas de pagamento. `Products` é montado exclusivamente a partir de
 * `order_items` — nunca de `product_variants` (RF-04).
 */
final class PaymentPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function base(Order $order): array
    {
        return [
            'IsSandbox' => (bool) config('services.safe2pay.is_sandbox'),
            'Application' => 'Digital Lock',
            'CallbackUrl' => url('/webhooks/safe2pay'),
            'PaymentMethod' => PaymentMethodCode::fromSlug($order->paymentMethod->slug),
            'Reference' => $order->number,
            'Customer' => $this->buildCustomer($order->customer),
            'Products' => $this->buildProducts($order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCustomer(Customer $customer): array
    {
        $address = $customer->addresses()->where('is_primary', true)->first();

        return [
            'Name' => $customer->legal_name,
            'Identity' => $customer->document,
            'Phone' => $customer->phone,
            'Email' => $customer->email,
            'Address' => $this->buildAddress($address),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAddress(?CustomerAddress $address): array
    {
        return [
            'ZipCode' => $address?->postal_code,
            'Street' => $address?->street,
            'Number' => $address?->number,
            'District' => $address?->neighborhood,
            'CityName' => $address?->city,
            'StateInitials' => $address?->state,
            'CountryName' => 'Brasil',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildProducts(Order $order): array
    {
        return $order->items
            ->map(fn (OrderItem $item): array => [
                'Code' => $item->sku_snapshot,
                'Description' => $item->name_snapshot,
                'UnitPrice' => (string) $item->unit_price,
                'Quantity' => $item->quantity,
            ])
            ->all();
    }
}
