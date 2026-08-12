<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_id', 'certificate_format_id', 'sku', 'validity_months', 'price', 'promotional_price',
    'promotion_starts_at', 'promotion_ends_at', 'is_active', 'is_default',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'promotional_price' => 'decimal:2',
            'promotion_starts_at' => 'datetime',
            'promotion_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<CertificateFormat, $this>
     */
    public function certificateFormat(): BelongsTo
    {
        return $this->belongsTo(CertificateFormat::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Coupon, $this>
     */
    public function restrictedCoupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'restricted_variant_id');
    }
}
