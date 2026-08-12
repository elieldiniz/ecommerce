<?php

namespace App\Models;

use Database\Factories\HolderTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class HolderType extends Model
{
    /** @use HasFactory<HolderTypeFactory> */
    use HasFactory;

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<IssuanceData, $this>
     */
    public function issuanceData(): HasMany
    {
        return $this->hasMany(IssuanceData::class);
    }
}
