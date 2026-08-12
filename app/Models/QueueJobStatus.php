<?php

namespace App\Models;

use Database\Factories\QueueJobStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug'])]
class QueueJobStatus extends Model
{
    /** @use HasFactory<QueueJobStatusFactory> */
    use HasFactory;

    /**
     * @return HasMany<IntegrationQueueJob, $this>
     */
    public function integrationQueueJobs(): HasMany
    {
        return $this->hasMany(IntegrationQueueJob::class, 'status_id');
    }
}
