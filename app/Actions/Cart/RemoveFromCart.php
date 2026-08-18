<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class RemoveFromCart
{
    /**
     * Remove an item from the cart.
     */
    public function execute(Cart $cart, int $cartItemId): void
    {
        DB::transaction(function () use ($cart, $cartItemId) {
            $cart->items()->where('id', $cartItemId)->delete();

            if ($cart->items()->count() === 0) {
                $cart->delete();
            }
        });
    }
}
