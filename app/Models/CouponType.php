<?php

namespace App\Models;

use Database\Factories\CouponTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class CouponType extends Model
{
    /** @use HasFactory<CouponTypeFactory> */
    use HasFactory;

    /**
     * @return HasMany<Coupon, $this>
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'type_id');
    }
}
