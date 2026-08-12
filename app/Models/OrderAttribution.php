<?php

namespace App\Models;

use Database\Factories\OrderAttributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'order_attribution', key: 'order_id', incrementing: false)]
#[Fillable([
    'order_id', 'device_type_id', 'gclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term',
    'utm_content', 'landing_page', 'referrer', 'first_touch_at', 'sessions_before_purchase',
])]
class OrderAttribution extends Model
{
    /** @use HasFactory<OrderAttributionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_touch_at' => 'datetime',
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
     * @return BelongsTo<DeviceType, $this>
     */
    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }
}
