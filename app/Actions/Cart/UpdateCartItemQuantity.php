<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class UpdateCartItemQuantity
{
    /**
     * Update the quantity of an item in the cart.
     */
    public function execute(Cart $cart, int $cartItemId, int $quantity): void
    {
        DB::transaction(function () use ($cart, $cartItemId, $quantity) {
            $item = $cart->items()->where('id', $cartItemId)->firstOrFail();

            if ($quantity <= 0) {
                $item->delete();

                if ($cart->items()->count() === 0) {
                    $cart->delete();
                }
            } else {
                $item->update(['quantity' => $quantity]);
            }
        });
    }
}
