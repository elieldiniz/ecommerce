<?php

namespace App\Models;

use Database\Factories\AdsConversionStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class AdsConversionStatus extends Model
{
    /** @use HasFactory<AdsConversionStatusFactory> */
    use HasFactory;

    /**
     * @return HasMany<AdsConversion, $this>
     */
    public function adsConversions(): HasMany
    {
        return $this->hasMany(AdsConversion::class, 'status_id');
    }
}
