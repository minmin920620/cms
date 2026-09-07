<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrimeStatusHistory extends Model
{
    protected $fillable = [
        'crime_id',
        'changed_by',
        'old_status',
        'new_status',
        'remarks',
    ];

    public function crime(): BelongsTo
    {
        return $this->belongsTo(Crime::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getOldStatusLabelAttribute(): string
    {
        return $this->old_status
            ? (Crime::STATUS_LABELS[$this->old_status] ?? ucfirst(str_replace('_', ' ', $this->old_status)))
            : 'New Record';
    }

    public function getNewStatusLabelAttribute(): string
    {
        return Crime::STATUS_LABELS[$this->new_status] ?? ucfirst(str_replace('_', ' ', $this->new_status));
    }
}
