<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_ADMIN = 'admin';
    const ROLE_POLICE_OFFICER = 'police_officer';
    const ROLE_INVESTIGATOR = 'investigator';
    const ROLE_LGU = 'lgu';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'badge_number',
        'avatar',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isPoliceOfficer(): bool
    {
        return $this->role === self::ROLE_POLICE_OFFICER;
    }

    public function isInvestigator(): bool
    {
        return $this->role === self::ROLE_INVESTIGATOR;
    }

    public function isLgu(): bool
    {
        return $this->role === self::ROLE_LGU;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function reportedCrimes()
    {
        return $this->hasMany(Crime::class, 'reported_by');
    }

    public function assignedCrimes()
    {
        return $this->hasMany(Crime::class, 'assigned_officer');
    }

    public function uploadedEvidence()
    {
        return $this->hasMany(Evidence::class, 'uploaded_by');
    }

    public function notifications()
    {
        return $this->hasMany(SystemNotification::class);
    }
}
