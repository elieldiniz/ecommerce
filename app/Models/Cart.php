<?php

namespace App\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'session_id', 'coupon_id'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get or create cart for a visitor (session-based).
     */
    public static function getOrCreateForVisitor(string $sessionId): self
    {
        return static::query()
            ->firstOrCreate(
                ['session_id' => $sessionId],
                ['session_id' => $sessionId]
            );
    }

    /**
     * Get or create cart for an authenticated customer.
     */
    public static function getOrCreateForCustomer(Customer $customer): self
    {
        return static::query()
            ->firstOrCreate(
                ['customer_id' => $customer->id],
                ['customer_id' => $customer->id]
            );
    }

    /**
     * Merge items from a session cart into this customer's cart.
     */
    public function mergeFromSession(string $sessionId): void
    {
        $sessionCart = static::query()
            ->where('session_id', $sessionId)
            ->with('items')
            ->first();

        if ($sessionCart === null) {
            return;
        }

        foreach ($sessionCart->items as $sessionItem) {
            $existingItem = $this->items()
                ->where('product_variant_id', $sessionItem->product_variant_id)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $sessionItem->quantity);
            } else {
                $this->items()->create([
                    'product_variant_id' => $sessionItem->product_variant_id,
                    'quantity' => $sessionItem->quantity,
                ]);
            }
        }

        $sessionCart->items()->delete();
        $sessionCart->delete();
    }
}
