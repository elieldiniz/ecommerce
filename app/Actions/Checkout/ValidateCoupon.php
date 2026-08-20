<?php

namespace App\Actions\Checkout;

use App\Models\Coupon;
use App\Models\CouponUse;
use App\Models\Customer;
use App\Models\ProductVariant;

class ValidateCoupon
{
    /**
     * Checa validade, limite de uso, limite por cliente e variante restrita (RF-29).
     * Reutilizada tanto pelo feedback em tempo real do checkout quanto pela gravação
     * atômica do pedido em CreateOrderFromCart — mesma regra, nunca duplicada.
     */
    public function execute(Coupon $coupon, ?ProductVariant $variant, ?Customer $customer): ?string
    {
        if (! $coupon->is_active) {
            return 'Este cupom está inativo.';
        }

        if ($coupon->starts_at !== null && now()->lt($coupon->starts_at)) {
            return 'Este cupom ainda não é válido.';
        }

        if ($coupon->ends_at !== null && now()->gt($coupon->ends_at)) {
            return 'Este cupom expirou.';
        }

        if ($coupon->usage_limit !== null && $coupon->uses_count >= $coupon->usage_limit) {
            return 'Este cupom atingiu o limite de usos.';
        }

        if ($coupon->restricted_variant_id !== null
            && (! $variant instanceof ProductVariant || $coupon->restricted_variant_id !== $variant->id)) {
            return 'Este cupom não é válido para o produto selecionado.';
        }

        if ($coupon->per_customer_limit !== null && $customer instanceof Customer) {
            $used = CouponUse::query()
                ->where('coupon_id', $coupon->id)
                ->where('customer_id', $customer->id)
                ->count();

            if ($used >= $coupon->per_customer_limit) {
                return 'Você já utilizou este cupom o número máximo de vezes permitido.';
            }
        }

        return null;
    }
}
