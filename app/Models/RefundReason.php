<?php

namespace App\Models;

use Database\Factories\RefundReasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class RefundReason extends Model
{
    /** @use HasFactory<RefundReasonFactory> */
    use HasFactory;

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'reason_id');
    }
}
