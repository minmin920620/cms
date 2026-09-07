<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Crime extends Model
{
    use SoftDeletes;

    protected $table = 'crime_incidents';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CIRAS_RECORDING = 'ciras_recording';
    public const STATUS_FOR_REVIEW = 'for_review';
    public const STATUS_FOR_CORRECTION = 'for_correction';
    public const STATUS_DATA_STORED = 'data_stored';
    public const STATUS_IRF_PRINTED = 'irf_printed';
    public const STATUS_FOR_SIGNATURE = 'for_signature';
    public const STATUS_BLOTTER_ENTERED = 'blotter_entered';
    public const STATUS_UCPER_COMPILED = 'ucper_compiled';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CIRAS_RECORDING,
        self::STATUS_FOR_REVIEW,
        self::STATUS_FOR_CORRECTION,
        self::STATUS_DATA_STORED,
        self::STATUS_IRF_PRINTED,
        self::STATUS_FOR_SIGNATURE,
        self::STATUS_BLOTTER_ENTERED,
        self::STATUS_UCPER_COMPILED,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CIRAS_RECORDING,
        self::STATUS_FOR_REVIEW,
        self::STATUS_FOR_CORRECTION,
        self::STATUS_DATA_STORED,
        self::STATUS_IRF_PRINTED,
        self::STATUS_FOR_SIGNATURE,
        self::STATUS_BLOTTER_ENTERED,
        self::STATUS_UCPER_COMPILED,
    ];

    public const COMPLETED_STATUSES = [
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public const APPROVAL_FINAL_STATUSES = [
        self::STATUS_DATA_STORED,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Report Received',
        self::STATUS_CIRAS_RECORDING => 'CIRAS Recording',
        self::STATUS_FOR_REVIEW => 'For Summary Review',
        self::STATUS_FOR_CORRECTION => 'For Correction',
        self::STATUS_DATA_STORED => 'Data Stored',
        self::STATUS_IRF_PRINTED => 'IRF Printed',
        self::STATUS_FOR_SIGNATURE => 'For IRF Signature',
        self::STATUS_BLOTTER_ENTERED => 'Blotter Entered',
        self::STATUS_UCPER_COMPILED => 'Compiled for UCPER',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_CLOSED => 'Closed',
    ];

    public const STATUS_COLORS = [
        self::STATUS_PENDING => '#f59e0b',
        self::STATUS_CIRAS_RECORDING => '#2563eb',
        self::STATUS_FOR_REVIEW => '#7c3aed',
        self::STATUS_FOR_CORRECTION => '#dc2626',
        self::STATUS_DATA_STORED => '#0891b2',
        self::STATUS_IRF_PRINTED => '#4f46e5',
        self::STATUS_FOR_SIGNATURE => '#9333ea',
        self::STATUS_BLOTTER_ENTERED => '#0f766e',
        self::STATUS_UCPER_COMPILED => '#16a34a',
        self::STATUS_RESOLVED => '#10b981',
        self::STATUS_CLOSED => '#6b7280',
    ];

    protected $fillable = [
        'case_number',
        'title',
        'crime_type_id',
        'barangay_id',
        'date_reported',
        'time_reported',
        'date_occurred',
        'time_occurred',
        'stage_of_felony',
        'offense_type_id',
        'status',
        'status_notes',
        'approval_status',
        'approval_requested_status',
        'approval_notes',
        'approval_requested_by',
        'approval_requested_at',
        'approved_by',
        'approved_at',
        'investigation_findings',
        'findings_recorded_at',
        'latitude',
        'longitude',
        'address',
        'reported_by',
        'assigned_officer',
        'resolved_at',
    ];

    protected $casts = [
        'date_reported' => 'date',
        'time_reported' => 'datetime:H:i',
        'date_occurred' => 'date',
        'time_occurred' => 'datetime:H:i',
        'resolved_at' => 'datetime',
        'findings_recorded_at' => 'datetime',
        'approval_requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Crime $crime) {
            if (empty($crime->case_number)) {
                $crime->case_number = static::generateCaseNumber();
            }
        });
    }

    public static function generateCaseNumber(): string
    {
        $maxAttempts = 5;
        $attempt = 0;

        do {
            $prefix = 'CRM-' . now()->format('Ymd');

            // Use withTrashed() to account for soft-deleted records that still occupy the unique index slot
            $lastCase = static::withTrashed()
                ->where('case_number', 'like', $prefix . '-%')
                ->orderBy('case_number', 'desc')
                ->first();

            if ($lastCase) {
                $lastNumber = (int) substr($lastCase->case_number, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $caseNumber = $prefix . '-' . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);

            // Verify the generated number doesn't already exist (handles race conditions)
            $exists = static::withTrashed()->where('case_number', $caseNumber)->exists();
            $attempt++;

            if (!$exists) {
                return $caseNumber;
            }

            // If it exists, try the next number in the next iteration
        } while ($attempt < $maxAttempts);

        // Fallback: add a random suffix if we exhausted attempts
        $suffix = now()->format('His') . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . '-' . $suffix;
    }

    public function crimeType(): BelongsTo
    {
        return $this->belongsTo(CrimeType::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function offenseType(): BelongsTo
    {
        return $this->belongsTo(OffenseType::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CrimeStatusHistory::class)->latest();
    }

    public function assignmentHistories(): HasMany
    {
        return $this->hasMany(CaseAssignmentHistory::class)->latest();
    }

    public function approvalRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnderInvestigation($query)
    {
        return $query->whereIn('status', [
            self::STATUS_CIRAS_RECORDING,
            self::STATUS_FOR_REVIEW,
            self::STATUS_FOR_CORRECTION,
            self::STATUS_DATA_STORED,
            self::STATUS_IRF_PRINTED,
            self::STATUS_FOR_SIGNATURE,
            self::STATUS_BLOTTER_ENTERED,
            self::STATUS_UCPER_COMPILED,
        ]);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeByType($query, $crimeTypeId)
    {
        return $query->where('crime_type_id', $crimeTypeId);
    }

    public function scopeByBarangay($query, $barangayId)
    {
        return $query->where('barangay_id', $barangayId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_occurred', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date_occurred', Carbon::today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date_occurred', Carbon::now()->month)
            ->whereYear('date_occurred', Carbon::now()->year);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getFormattedInvestigationFindingsAttribute(): string
    {
        if (blank($this->investigation_findings)) {
            return '<p>No investigation findings recorded yet.</p>';
        }

        $html = [];
        $inList = false;
        $lines = preg_split('/\R/', (string) $this->investigation_findings);

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }

                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                if (! $inList) {
                    $html[] = '<ul>';
                    $inList = true;
                }

                $html[] = '<li>' . $this->formatFindingLine($matches[1]) . '</li>';
                continue;
            }

            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }

            $html[] = '<p>' . $this->formatFindingLine($trimmed) . '</p>';
        }

        if ($inList) {
            $html[] = '</ul>';
        }

        return implode('', $html);
    }

    private function formatFindingLine(string $line): string
    {
        $escaped = e($line);

        return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
    }

    public function getNextWorkflowStatusAttribute(): ?string
    {
        $nextStatuses = [
            self::STATUS_PENDING => self::STATUS_CIRAS_RECORDING,
            self::STATUS_CIRAS_RECORDING => self::STATUS_FOR_REVIEW,
            self::STATUS_FOR_REVIEW => self::STATUS_DATA_STORED,
            self::STATUS_FOR_CORRECTION => self::STATUS_FOR_REVIEW,
            self::STATUS_DATA_STORED => self::STATUS_IRF_PRINTED,
            self::STATUS_IRF_PRINTED => self::STATUS_FOR_SIGNATURE,
            self::STATUS_FOR_SIGNATURE => self::STATUS_BLOTTER_ENTERED,
            self::STATUS_BLOTTER_ENTERED => self::STATUS_UCPER_COMPILED,
        ];

        return $nextStatuses[$this->status] ?? null;
    }
}

