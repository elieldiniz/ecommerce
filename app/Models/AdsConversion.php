<?php

namespace App\Models;

use Database\Factories\AdsConversionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'status_id', 'transaction_id', 'gclid', 'amount', 'currency', 'attempts', 'sent_at', 'response',
])]
class AdsConversion extends Model
{
    /** @use HasFactory<AdsConversionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'sent_at' => 'datetime',
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
     * @return BelongsTo<AdsConversionStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(AdsConversionStatus::class, 'status_id');
    }
}
