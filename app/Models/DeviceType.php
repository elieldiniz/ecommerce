<?php

namespace App\Models;

use Database\Factories\DeviceTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class DeviceType extends Model
{
    /** @use HasFactory<DeviceTypeFactory> */
    use HasFactory;

    /**
     * @return HasMany<OrderAttribution, $this>
     */
    public function orderAttributions(): HasMany
    {
        return $this->hasMany(OrderAttribution::class);
    }
}
