<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffenseType extends Model
{
    public const DEFAULT_OFFENSES = [
        'THEFT- RPC Art. 308',
        'ANTI-RAPE LAW OF 1997 - RA 8353',
        'NEW ANTI-CARNAPPING ACT OF 2016 - MC-RA 10883(repealed RA 6539)',
        'QUALIFIED THEFT - RPC Art. 310 as amended by BP Blg 71',
        'PARRICIDE -RPC Art. 246',
        'LESS SERIOUS PHYSICAL INJURIES - RPC Art. 265',
        'MURDER -RPC Art. 248',
        'ROBBERY - RPC Art. 293',
        'HOMICIDE - RPC Art. 249',
    ];

    protected $fillable = [
        'name',
        'crime_type_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function crimeType(): BelongsTo
    {
        return $this->belongsTo(CrimeType::class);
    }

    public function crimes(): HasMany
    {
        return $this->hasMany(Crime::class);
    }

    public static function offenseChoices(?int $includeId = null)
    {
        return static::query()
            ->where(function ($query) use ($includeId) {
                $query->where('is_active', true);

                if ($includeId) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'crime_type_id']);
    }

    public static function offenseOptions(?string $includeName = null)
    {
        return static::query()
            ->where(function ($query) use ($includeName) {
                $query->where('is_active', true);

                if ($includeName) {
                    $query->orWhere('name', $includeName);
                }
            })
            ->orderBy('name')
            ->pluck('name')
            ->values();
    }
}
