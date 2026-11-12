<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\ReservationSchedule;
use App\Services\ConflictChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ReservationController extends Controller
{
    public function __construct(protected ConflictChecker $conflictChecker)
    {
    }

    // GET /api/reservations?status=pending
    public function index(Request $request)
    {
        $query = Reservation::with(['division', 'schedules'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return $query->get()->map(fn ($r) => $this->transform($r));
    }

    // GET /api/reservations/{reservation}
    public function show(Reservation $reservation)
    {
        return $this->transform($reservation->load(['division', 'schedules']));
    }

    // GET /api/reservations/track/{reference}
    // Lookup by reference code
    public function track(string $reference)
    {
        $reservation = Reservation::with(['division', 'schedules'])
            ->where('reference_no', strtoupper(trim($reference)))
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'No request found with that code.'], 404);
        }

        return $this->transform($reservation);
    }

    // POST /api/reservations/check-conflict
    // body: { schedule: [{date, from, to}], exclude_id?: number }
    public function checkConflict(Request $request)
    {
        $data = $request->validate([
            'schedule'         => ['required', 'array', 'min:1'],
            'schedule.*.date'  => ['required', 'date'],
            'schedule.*.from'  => ['required', 'date_format:H:i'],
            'schedule.*.to'    => ['required', 'date_format:H:i'],
            'exclude_id'       => ['nullable', 'integer'],
        ]);

        return $this->conflictChecker->check($data['schedule'], $data['exclude_id'] ?? null);
    }

    // POST /api/reservations
    public function store(StoreReservationRequest $request)
    {
        $validated = $request->validated();

        // Always re-check on the server — never trust the client-side check alone
        $result = $this->conflictChecker->check($validated['schedule']);
        if (!$result['clean']) {
            return response()->json([
                'message'   => 'This time slot conflicts with an existing booking.',
                'conflicts' => $result['conflicts'],
            ], 409);
        }

        // save the drawn signature as a PNG file, keep only its path
        $signaturePath = $this->saveSignature($validated['signature']);

        $reservation = DB::transaction(function () use ($validated, $signaturePath) {
            $reservation = Reservation::create([
                'reference_no'      => Reservation::generateReferenceNo(),
                'requester_name'    => $validated['requester_name'],
                'division_id'       => $validated['division_id'] ?? null,
                'division_other'    => $validated['division_other'] ?? null,
                'date_of_filing'    => now()->toDateString(),
                'participants'      => $validated['participants'],
                'purpose'           => $validated['purpose'],
                'purpose_title'     => $validated['purpose_title'],
                'extras'            => $validated['extras'] ?? [],
                'setup_requirement' => $validated['setup_requirement'] ?? null,
                'other_needs'       => $validated['other_needs'] ?? null,
                'signature'         => $signaturePath,
                'status'            => Reservation::STATUS_PENDING,
            ]);

            foreach ($validated['schedule'] as $slot) {
                $reservation->schedules()->create([
                    'booking_date' => $slot['date'],
                    'time_from'    => $slot['from'],
                    'time_to'      => $slot['to'],
                ]);
            }

            return $reservation;
        });

        return response()->json($this->transform($reservation->load(['division', 'schedules'])), 201);
    }

    // PATCH /api/reservations/{reservation}/status
    public function updateStatus(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'status'        => ['required', 'in:pending,approved,rejected,archived'],
            'approved_by'   => ['nullable', 'string', 'max:150'],
            'decision_note' => ['nullable', 'string', 'max:255'],
        ]);

        $reservation->update([
            'status'        => $data['status'],
            'approved_by'   => $data['approved_by'] ?? $reservation->approved_by,
            'decision_note' => $data['decision_note'] ?? $reservation->decision_note,
            'decided_at'    => now(),
        ]);

        return $this->transform($reservation->fresh(['division', 'schedules']));
    }

    // GET /api/reservations/calendar?year=&month=
    public function calendar(Request $request)
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $schedules = ReservationSchedule::query()
            ->whereYear('booking_date', $year)
            ->whereMonth('booking_date', $month)
            ->whereHas('reservation', fn ($q) => $q->whereIn('status', Reservation::BLOCKING_STATUSES))
            ->with('reservation:id,reference_no,requester_name,purpose_title,status')
            ->orderBy('time_from')
            ->get();

        $byDate = $schedules->groupBy(fn ($s) => $s->booking_date->format('Y-m-d'));

        return $byDate->map(function ($items, $date) {
            $blocks = $items->map(fn ($s) => [
                'reservation_id' => $s->reservation_id,
                'reference_no'   => $s->reservation->reference_no,
                'requester'      => $s->reservation->requester_name,
                'title'          => $s->reservation->purpose_title,
                'status'         => $s->reservation->status,
                'from'           => substr($s->time_from, 0, 5),
                'to'             => substr($s->time_to, 0, 5),
            ])->values();

            // Flag any block that overlaps another block on the same day
            $flagged = $blocks->map(function ($b) use ($blocks) {
                $overlaps = $blocks->filter(fn ($o) =>
                    $o['reservation_id'] !== $b['reservation_id'] &&
                    $o['from'] < $b['to'] && $b['from'] < $o['to']
                )->values();
                $b['has_conflict'] = $overlaps->isNotEmpty();
                $b['conflicts_with'] = $overlaps;
                return $b;
            });

            return ['date' => $date, 'count' => $blocks->count(), 'blocks' => $flagged];
        })->values();
    }

    // GET /api/reservations/{reservation}/pdf
    public function pdf(Reservation $reservation)
    {
        $reservation->load(['division', 'schedules']);
        $pdf = Pdf::loadView('pdf.reservation', ['r' => $reservation]);
        return $pdf->stream($reservation->reference_no . '.pdf');
    }

    protected function transform(Reservation $r): array
    {
        return [
            'id'                => $r->id,
            'reference_no'      => $r->reference_no,
            'requester_name'    => $r->requester_name,
            'division_name'     => $r->division_name,
            'date_of_filing'    => optional($r->date_of_filing)->format('Y-m-d'),
            'participants'      => $r->participants,
            'purpose'           => $r->purpose,
            'purpose_title'     => $r->purpose_title,
            'extras'            => $r->extras ?? [],
            'setup_requirement' => $r->setup_requirement,
            'other_needs'       => $r->other_needs,
            'status'            => $r->status,
            'approved_by'       => $r->approved_by,
            'decision_note'     => $r->decision_note,
            'decided_at'        => optional($r->decided_at)->toIso8601String(),
            'signature'         => $this->signatureUrl($r),
            'schedule'          => $r->schedules->map(fn ($s) => [
                'date' => $s->booking_date->format('Y-m-d'),
                'from' => substr($s->time_from, 0, 5),
                'to'   => substr($s->time_to, 0, 5),
            ])->values(),
        ];
    }

    // "data:image/png;base64,AAAA..."  ->  storage/app/public/signatures/xxxx.png
    // returns the path that gets saved in the database
    protected function saveSignature(string $dataUri): string
    {
        $prefix = 'data:image/png;base64,';

        if (!str_starts_with($dataUri, $prefix)) {
            abort(422, 'The signature must be a PNG image.');
        }

        $binary = base64_decode(substr($dataUri, strlen($prefix)), true);

        // every PNG file starts with these 4 bytes
        if ($binary === false || !str_starts_with($binary, "\x89PNG")) {
            abort(422, 'The signature image is not valid.');
        }

        // random name, so nobody can guess another person's signature URL
        $path = 'signatures/' . Str::random(40) . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    // path in the database -> full URL the browser can load as an <img>
    protected function signatureUrl(Reservation $r): ?string
    {
        if (!$r->signature) {
            return null;
        }

        // rows saved before this change still hold the whole base64 image
        if (str_starts_with($r->signature, 'data:image')) {
            return $r->signature;
        }

        return asset('storage/' . $r->signature);
    }
}