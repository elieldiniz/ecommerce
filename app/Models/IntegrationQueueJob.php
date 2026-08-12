<?php

namespace App\Models;

use Database\Factories\IntegrationQueueJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'integration_queue')]
#[Fillable(['status_id', 'job', 'reference_type', 'reference_id', 'payload', 'attempts', 'run_at', 'error'])]
class IntegrationQueueJob extends Model
{
    /** @use HasFactory<IntegrationQueueJobFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'run_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QueueJobStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(QueueJobStatus::class, 'status_id');
    }
}
