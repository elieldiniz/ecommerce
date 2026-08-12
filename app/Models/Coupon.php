<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'type_id', 'restricted_variant_id', 'value', 'usage_limit', 'uses_count',
    'per_customer_limit', 'starts_at', 'ends_at', 'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CouponType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CouponType::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function restrictedVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'restricted_variant_id');
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<CouponUse, $this>
     */
    public function uses(): HasMany
    {
        return $this->hasMany(CouponUse::class);
    }
}
