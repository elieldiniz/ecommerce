<?php

namespace App\Models;

use Database\Factories\GfsisStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class GfsisStatus extends Model
{
    /** @use HasFactory<GfsisStatusFactory> */
    use HasFactory;

    /**
     * @return HasMany<OrderItemGfsis, $this>
     */
    public function orderItemGfsisRecords(): HasMany
    {
        return $this->hasMany(OrderItemGfsis::class, 'status_id');
    }
}
