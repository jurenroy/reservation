<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ARCHIVED = 'archived';

    // Statuses that occupy the room and must be checked for conflicts
    const BLOCKING_STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED];

    protected $fillable = [
        'reference_no', 'requester_name', 'division_id', 'division_other',
        'date_of_filing', 'participants', 'purpose', 'purpose_title',
        'extras', 'setup_requirement', 'other_needs', 'signature',
        'status', 'approved_by', 'decided_at', 'decision_note',
    ];

    protected $casts = [
        'extras'         => 'array',
        'date_of_filing' => 'date',
        'decided_at'     => 'datetime',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReservationSchedule::class)->orderBy('booking_date');
    }

    public function getDivisionNameAttribute(): string
    {
        if ($this->division_other) {
            return 'Others — ' . $this->division_other;
        }
        return $this->division->name ?? '—';
    }

    public static function generateReferenceNo(): string
    {
        return 'MGB-' . now()->format('ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
