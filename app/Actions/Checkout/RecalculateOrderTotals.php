<?php

namespace App\Actions\Checkout;

use App\Models\Coupon;
use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

class RecalculateOrderTotals
{
    /**
     * Calcula os totais do pedido a partir dos itens do carrinho, cupom e forma de pagamento (RF-03, RF-28).
     * Reaproveitada tanto na criação do pedido quanto no guard anti-divergência — mesma lógica, nunca duplicada.
     *
     * @param  Collection<int, array{unit_price: string, quantity: int}>  $cartItems
     * @return array{subtotal: string, coupon_discount: string, payment_method_discount: string, total: string}
     */
    public function execute(Collection $cartItems, PaymentMethod $paymentMethod, ?Coupon $coupon = null): array
    {
        $subtotal = $cartItems->reduce(
            fn (string $carry, array $item): string => bcadd($carry, bcmul((string) $item['unit_price'], (string) $item['quantity'], 2), 2),
            '0.00',
        );

        $couponDiscount = $this->couponDiscount($subtotal, $coupon);
        $afterCouponDiscount = bcsub($subtotal, $couponDiscount, 2);

        $paymentMethodDiscount = bcmul(
            $afterCouponDiscount,
            bcdiv((string) $paymentMethod->discount_percentage, '100', 4),
            2,
        );

        $total = bcsub($afterCouponDiscount, $paymentMethodDiscount, 2);

        return [
            'subtotal' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'payment_method_discount' => $paymentMethodDiscount,
            'total' => $total,
        ];
    }

    private function couponDiscount(string $subtotal, ?Coupon $coupon): string
    {
        if ($coupon === null) {
            return '0.00';
        }

        $discount = $coupon->type->slug === 'percentage'
            ? bcmul($subtotal, bcdiv((string) $coupon->value, '100', 4), 2)
            : (string) $coupon->value;

        return bccomp($discount, $subtotal, 2) === 1 ? $subtotal : $discount;
    }
}
