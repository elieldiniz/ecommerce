<?php

namespace App\Models;

use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payment_id', 'reason_id', 'user_id', 'amount', 'requires_revocation', 'revocation_confirmed_at',
    'requested_at', 'completed_at',
])]
class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requires_revocation' => 'boolean',
            'revocation_confirmed_at' => 'datetime',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<RefundReason, $this>
     */
    public function reason(): BelongsTo
    {
        return $this->belongsTo(RefundReason::class, 'reason_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
