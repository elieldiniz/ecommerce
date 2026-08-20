<?php

namespace App\Actions\Checkout;

use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderFulfillmentStatus;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateOrderFromCart
{
    /**
     * Cria o pedido e o snapshot dos itens antes de qualquer chamada à Safe2Pay (RF-01, RF-02, RF-03).
     * Nunca referencia a variante depois de gravar o snapshot em `order_items`, nem depende de rede.
     *
     * @param  Collection<int, array{product_variant_id: int, quantity: int}>  $cartItems
     */
    public function execute(
        Customer $customer,
        Collection $cartItems,
        PaymentMethod $paymentMethod,
        ?Coupon $coupon,
        string $ipAddress,
        string $userAgent,
    ): Order {
        return DB::transaction(function () use ($customer, $cartItems, $paymentMethod, $coupon, $ipAddress, $userAgent) {
            $variants = ProductVariant::query()
                ->with(['product', 'certificateFormat'])
                ->whereIn('id', $cartItems->pluck('product_variant_id'))
                ->get()
                ->keyBy('id');

            $snapshotItems = $cartItems->map(function (array $item) use ($variants) {
                $variant = $variants->get($item['product_variant_id']);

                return [
                    'product_variant_id' => $variant->id,
                    'sku_snapshot' => $variant->sku,
                    'name_snapshot' => trim($variant->product->name.' '.$variant->certificateFormat->name),
                    'list_price_snapshot' => (string) $variant->price,
                    'unit_price' => $this->resolveUnitPrice($variant),
                    'quantity' => $item['quantity'],
                ];
            });

            $appliedCoupon = null;

            if ($coupon !== null) {
                $lockedCoupon = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->first();

                if ($lockedCoupon !== null && (new ValidateCoupon)->execute($lockedCoupon, $variants->first(), $customer) === null) {
                    $appliedCoupon = $lockedCoupon;
                }
            }

            $totals = (new RecalculateOrderTotals)->execute($snapshotItems, $paymentMethod, $appliedCoupon);

            $order = Order::query()->create([
                'number' => $this->generateNumber(),
                'customer_id' => $customer->id,
                'status_id' => OrderStatus::query()->where('slug', 'awaiting_payment')->value('id'),
                'fulfillment_status_id' => OrderFulfillmentStatus::query()->where('slug', 'awaiting_data')->value('id'),
                'payment_method_id' => $paymentMethod->id,
                'coupon_id' => $appliedCoupon?->id,
                'subtotal' => $totals['subtotal'],
                'coupon_discount' => $totals['coupon_discount'],
                'payment_method_discount' => $totals['payment_method_discount'],
                'total' => $totals['total'],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            foreach ($snapshotItems as $item) {
                $order->items()->create([
                    'product_variant_id' => $item['product_variant_id'],
                    'sku_snapshot' => $item['sku_snapshot'],
                    'name_snapshot' => $item['name_snapshot'],
                    'list_price_snapshot' => $item['list_price_snapshot'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'total' => bcmul((string) $item['unit_price'], (string) $item['quantity'], 2),
                ]);
            }

            if ($appliedCoupon !== null) {
                CouponUse::create([
                    'coupon_id' => $appliedCoupon->id,
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'discount_applied' => $totals['coupon_discount'],
                ]);
                $appliedCoupon->increment('uses_count');
            }

            return $order->load('items');
        });
    }

    private function resolveUnitPrice(ProductVariant $variant): string
    {
        $now = now();

        $hasActivePromotion = $variant->promotional_price !== null
            && ($variant->promotion_starts_at === null || $variant->promotion_starts_at->lte($now))
            && ($variant->promotion_ends_at === null || $variant->promotion_ends_at->gte($now));

        return $hasActivePromotion ? (string) $variant->promotional_price : (string) $variant->price;
    }

    private function generateNumber(): string
    {
        do {
            $number = 'PED-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
