<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'order_id', 'product_variant_id', 'sku_snapshot', 'name_snapshot', 'list_price_snapshot',
    'unit_price', 'quantity', 'total',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'list_price_snapshot' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return HasOne<IssuanceData, $this>
     */
    public function issuanceData(): HasOne
    {
        return $this->hasOne(IssuanceData::class);
    }

    /**
     * @return HasOne<OrderItemGfsis, $this>
     */
    public function gfsis(): HasOne
    {
        return $this->hasOne(OrderItemGfsis::class);
    }
}
