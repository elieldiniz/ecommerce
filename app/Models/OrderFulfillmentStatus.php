<?php

namespace App\Models;

use Database\Factories\OrderFulfillmentStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class OrderFulfillmentStatus extends Model
{
    /** @use HasFactory<OrderFulfillmentStatusFactory> */
    use HasFactory;

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'fulfillment_status_id');
    }
}
