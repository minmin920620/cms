<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evidence extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'evidence' => 'Evidence',
        'irf' => 'Signed IRF',
        'blotter' => 'Blotter Copy',
        'ucper' => 'UCPER File',
        'image' => 'Image',
        'document' => 'Document',
    ];

    protected $fillable = [
        'crime_id',
        'file_path',
        'storage_disk',
        'file_type',
        'file_name',
        'category',
        'description',
        'uploaded_by',
    ];

    public function crime(): BelongsTo
    {
        return $this->belongsTo(Crime::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }
}

