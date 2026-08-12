<?php

namespace App\Models;

use Database\Factories\GfsisEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gfsis_order_id', 'event_hash', 'received_status', 'payload', 'received_at', 'processed_at', 'error'])]
class GfsisEvent extends Model
{
    /** @use HasFactory<GfsisEventFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItemGfsis, $this>
     */
    public function orderItemGfsis(): BelongsTo
    {
        return $this->belongsTo(OrderItemGfsis::class, 'gfsis_order_id', 'gfsis_order_id');
    }
}
