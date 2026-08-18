<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SyncCartOnLogin
{
    /**
     * Sync the session cart into the customer's cart when they log in.
     */
    public function execute(Customer $customer, ?string $sessionId): Cart
    {
        return DB::transaction(function () use ($customer, $sessionId) {
            $cart = Cart::getOrCreateForCustomer($customer);

            if ($sessionId) {
                $cart->mergeFromSession($sessionId);
            }

            return $cart->load('items.productVariant.product', 'items.productVariant.certificateFormat');
        });
    }
}
