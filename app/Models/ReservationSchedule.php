<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationSchedule extends Model
{
    protected $fillable = ['reservation_id', 'booking_date', 'time_from', 'time_to'];

    protected $casts = [
        'booking_date' => 'date',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
