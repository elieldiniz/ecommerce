<?php

namespace App\Models;

use Database\Factories\OrderItemGfsisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'order_item_gfsis')]
#[Fillable([
    'order_item_id', 'status_id', 'gfsis_order_id', 'gfsis_code', 'status_synced_at', 'appointment_id',
    'appointment_date', 'appointment_time', 'certificate_expires_at', 'sent_at', 'attempts', 'last_error',
    'request_payload', 'response_payload',
])]
class OrderItemGfsis extends Model
{
    /** @use HasFactory<OrderItemGfsisFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_synced_at' => 'datetime',
            'appointment_date' => 'date',
            'certificate_expires_at' => 'date',
            'sent_at' => 'datetime',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<GfsisStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(GfsisStatus::class, 'status_id');
    }

    /**
     * @return HasMany<GfsisEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(GfsisEvent::class, 'gfsis_order_id', 'gfsis_order_id');
    }
}
