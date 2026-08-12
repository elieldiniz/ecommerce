<?php

namespace App\Models;

use Database\Factories\IssuanceDataFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'issuance_data')]
#[Fillable([
    'order_item_id', 'holder_type_id', 'holder_name', 'document', 'birth_date', 'email', 'phone',
    'postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'ibge_code',
    'responsible_name', 'responsible_document', 'responsible_birth_date', 'responsible_email',
    'responsible_phone', 'access_token', 'access_token_expires_at', 'filled_at',
])]
class IssuanceData extends Model
{
    /** @use HasFactory<IssuanceDataFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'responsible_birth_date' => 'date',
            'access_token_expires_at' => 'datetime',
            'filled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<HolderType, $this>
     */
    public function holderType(): BelongsTo
    {
        return $this->belongsTo(HolderType::class);
    }
}
