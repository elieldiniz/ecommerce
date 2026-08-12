<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'number', 'customer_id', 'status_id', 'fulfillment_status_id', 'payment_method_id', 'coupon_id',
    'subtotal', 'coupon_discount', 'payment_method_discount', 'total', 'ip_address', 'user_agent',
    'paid_at', 'cancelled_at', 'internal_notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'coupon_discount' => 'decimal:2',
            'payment_method_discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<OrderStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    /**
     * @return BelongsTo<OrderFulfillmentStatus, $this>
     */
    public function fulfillmentStatus(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillmentStatus::class, 'fulfillment_status_id');
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasOne<OrderAttribution, $this>
     */
    public function attribution(): HasOne
    {
        return $this->hasOne(OrderAttribution::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<CouponUse, $this>
     */
    public function couponUses(): HasMany
    {
        return $this->hasMany(CouponUse::class);
    }

    /**
     * @return HasOne<AdsConversion, $this>
     */
    public function adsConversion(): HasOne
    {
        return $this->hasOne(AdsConversion::class);
    }
}
