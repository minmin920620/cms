<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseAssignmentHistory extends Model
{
    protected $fillable = [
        'crime_id',
        'old_user_id',
        'new_user_id',
        'changed_by',
        'remarks',
    ];

    public function crime(): BelongsTo
    {
        return $this->belongsTo(Crime::class);
    }

    public function oldUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'old_user_id');
    }

    public function newUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_user_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
