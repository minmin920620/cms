<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Barangay extends Model
{
    protected $fillable = [
        'name',
        'city',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function crimes(): HasMany
    {
        return $this->hasMany(Crime::class);
    }

    public function latestCrime(): HasOne
    {
        return $this->hasOne(Crime::class)->latestOfMany('date_occurred');
    }
}

