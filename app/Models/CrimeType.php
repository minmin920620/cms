<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrimeType extends Model
{
    public const DEFAULT_CRIME_TYPES = [
        'Theft',
        'Robbery',
        'Assault',
        'Homicide',
        'Drug-Related',
        'Cyber Crime',
        'Fraud',
        'Physical Injury',
        'Sexual Offense',
        'Traffic Violation',
        'Vandalism',
        'Other',
    ];

    public const CRIME_TYPES = self::DEFAULT_CRIME_TYPES;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function crimes(): HasMany
    {
        return $this->hasMany(Crime::class);
    }

    public function offenseTypes(): HasMany
    {
        return $this->hasMany(OffenseType::class);
    }

    public static function crimeTypes(?int $includeId = null)
    {
        $query = static::query()
            ->where(function ($query) use ($includeId) {
                $query->where('is_active', true);

                if ($includeId) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get();

        return $query->values();
    }
}

