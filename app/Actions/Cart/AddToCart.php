<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class AddToCart
{
    /**
     * Add an item to the cart.
     *
     * For visitors: uses session_id
     * For authenticated customers: uses customer_id
     */
    public function execute(?Customer $customer, ?string $sessionId, int $productVariantId, int $quantity = 1): Cart
    {
        return DB::transaction(function () use ($customer, $sessionId, $productVariantId, $quantity) {
            if ($customer) {
                $cart = Cart::getOrCreateForCustomer($customer);
            } else {
                $cart = Cart::getOrCreateForVisitor($sessionId);
            }

            $existingItem = $cart->items()
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $quantity);
            } else {
                $cart->items()->create([
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity,
                ]);
            }

            return $cart->load('items.productVariant.product', 'items.productVariant.certificateFormat');
        });
    }
}
