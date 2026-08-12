<?php

namespace App\Models;

use Database\Factories\CertificateFormatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'requires_hardware'])]
class CertificateFormat extends Model
{
    /** @use HasFactory<CertificateFormatFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_hardware' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
