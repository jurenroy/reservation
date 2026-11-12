<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationSchedule;

class ConflictChecker
{
    /**
     * @param array $slots  [['date' => 'Y-m-d', 'from' => 'H:i', 'to' => 'H:i'], ...]
     * @param int|null $excludeReservationId  ignore this reservation's own slots (used on edit)
     *
     * @return array  ['clean' => bool, 'conflicts' => [ ['date'=>, 'from'=>, 'to'=>, 'with' => [...] ], ... ] ]
     */
    public function check(array $slots, ?int $excludeReservationId = null): array
    {
        $conflicts = [];

        foreach ($slots as $slot) {
            $date = $slot['date'];
            $from = $slot['from'];
            $to   = $slot['to'];

            $overlapping = ReservationSchedule::query()
                ->whereDate('booking_date', $date)
                // strict overlap: existing.from < new.to  AND  new.from < existing.to
                ->where('time_from', '<', $to)
                ->where('time_to', '>', $from)
                ->whereHas('reservation', function ($q) use ($excludeReservationId) {
                    $q->whereIn('status', Reservation::BLOCKING_STATUSES);
                    if ($excludeReservationId) {
                        $q->where('id', '!=', $excludeReservationId);
                    }
                })
                ->with('reservation:id,reference_no,requester_name,purpose,purpose_title,status')
                ->get();

            if ($overlapping->isNotEmpty()) {
                $conflicts[] = [
                    'date' => $date,
                    'from' => $from,
                    'to'   => $to,
                    'with' => $overlapping->map(function (ReservationSchedule $s) {
                        return [
                            'reference_no'  => $s->reservation->reference_no,
                            'requester'     => $s->reservation->requester_name,
                            'purpose_title' => $s->reservation->purpose_title,
                            'status'        => $s->reservation->status,
                            'time_from'     => substr($s->time_from, 0, 5),
                            'time_to'       => substr($s->time_to, 0, 5),
                        ];
                    })->values(),
                ];
            }
        }

        return [
            'clean'     => count($conflicts) === 0,
            'conflicts' => $conflicts,
        ];
    }
}
