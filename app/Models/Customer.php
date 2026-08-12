<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'holder_type_id', 'legal_name', 'document', 'email', 'phone', 'password_hash',
    'email_verified_at', 'terms_accepted_at', 'marketing_opt_in',
])]
#[Hidden(['password_hash'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'marketing_opt_in' => 'boolean',
            'password_hash' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<HolderType, $this>
     */
    public function holderType(): BelongsTo
    {
        return $this->belongsTo(HolderType::class);
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<CouponUse, $this>
     */
    public function couponUses(): HasMany
    {
        return $this->hasMany(CouponUse::class);
    }
}
