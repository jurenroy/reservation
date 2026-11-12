<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body{ font-family: DejaVu Sans, sans-serif; font-size:11px; color:#22281F; margin:0; padding:26px 34px; }
  .head{ text-align:center; margin-bottom:14px; }
  .head .agency{ font-size:11px; line-height:1.5; }
  .head .agency b{ display:block; }
  .head h1{ font-size:15px; margin:14px 0 2px; letter-spacing:.03em; }
  .head .code{ position:absolute; top:26px; right:34px; font-size:9px; color:#666; }
  table.fields{ width:100%; border-collapse:collapse; margin-top:10px; }
  table.fields td{ padding:5px 4px; vertical-align:top; }
  .label{ font-weight:bold; width:150px; }
  .value{ border-bottom:1px solid #999; }
  .section-title{ font-weight:bold; margin-top:16px; margin-bottom:6px; font-size:11.5px; text-transform:uppercase; color:#1B4332; border-bottom:1.5px solid #1B4332; padding-bottom:3px;}
  table.schedule{ width:100%; border-collapse:collapse; margin-top:6px; }
  table.schedule th, table.schedule td{ border:1px solid #ccc; padding:5px 8px; font-size:10.5px; text-align:left; }
  table.schedule th{ background:#EAF3EC; color:#1B4332; }
  .chips span{ display:inline-block; background:#EAF3EC; border:1px solid #1B4332; border-radius:10px; padding:2px 8px; margin:2px 4px 2px 0; font-size:10px; }
  .status{ display:inline-block; padding:3px 10px; border-radius:10px; font-weight:bold; font-size:10.5px; }
  .status-pending{ background:#FCEFD1; color:#8A6116; }
  .status-approved{ background:#DDEFE1; color:#1B4332; }
  .status-rejected{ background:#F6DCD8; color:#A23B2E; }
  .status-archived{ background:#E6E6E6; color:#555; }
  .sig-block{ margin-top:26px; }
  .sig-block img{ height:70px; display:block; margin-bottom:2px; }
  .sig-line{ width:260px; border-top:1px solid #333; padding-top:3px; font-size:10px; }
  .footer-note{ margin-top:26px; font-size:9px; color:#777; text-align:right; }
</style>
</head>
<body>

  <span class="code">MGB-X-FAD-FO-036</span>

  <div class="head">
    <div class="agency">
      Republic of the Philippines<br>
      Department of Environment and Natural Resources<br>
      <b>MINES AND GEOSCIENCES BUREAU</b>
      <b>Regional Office No. X</b>
      DENR-X Compound, Puntod, Cagayan de Oro City
    </div>
    <h1>CONFERENCE ROOM RESERVATION REQUEST</h1>
    <div>Reference No.: <b>{{ $r->reference_no }}</b> &nbsp;&nbsp;|&nbsp;&nbsp;
      Status: <span class="status status-{{ $r->status }}">{{ strtoupper($r->status) }}</span>
    </div>
  </div>

  <table class="fields">
    <tr>
      <td class="label">Name of Requester</td>
      <td class="value">{{ $r->requester_name }}</td>
      <td class="label" style="width:100px;">Date Filed</td>
      <td class="value" style="width:120px;">{{ optional($r->date_of_filing)->format('M d, Y') }}</td>
    </tr>
    <tr>
      <td class="label">Division/Section</td>
      <td class="value" colspan="3">{{ $r->division_name }}</td>
    </tr>
    <tr>
      <td class="label">Purpose</td>
      <td class="value" colspan="3">{{ $r->purpose }} — {{ $r->purpose_title }}</td>
    </tr>
    <tr>
      <td class="label">No. of Participants</td>
      <td class="value">{{ $r->participants }}</td>
      <td class="label">Set-up Requirement</td>
      <td class="value">{{ $r->setup_requirement ?: 'None' }}</td>
    </tr>
    <tr>
      <td class="label" style="vertical-align:top;">Equipment / Food</td>
      <td class="value" colspan="3">
        <div class="chips">
          @forelse($r->extras as $e) <span>{{ $e }}</span> @empty None @endforelse
        </div>
      </td>
    </tr>
    @if($r->other_needs)
    <tr>
      <td class="label">Other Needs</td>
      <td class="value" colspan="3">{{ $r->other_needs }}</td>
    </tr>
    @endif
  </table>

  <div class="section-title">Booking Schedule</div>
  <table class="schedule">
    <thead>
      <tr><th>Date</th><th>Time From</th><th>Time To</th></tr>
    </thead>
    <tbody>
      @foreach($r->schedules as $s)
      <tr>
        <td>{{ \Carbon\Carbon::parse($s->booking_date)->format('D, M d, Y') }}</td>
        <td>{{ \Carbon\Carbon::parse($s->time_from)->format('g:i A') }}</td>
        <td>{{ \Carbon\Carbon::parse($s->time_to)->format('g:i A') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="sig-block">
    @if($r->signature)
      <img src="{{ $r->signature }}" alt="signature">
    @endif
    <div class="sig-line">Requester's Signature — {{ $r->requester_name }}</div>
  </div>

  @if($r->status !== 'pending')
  <div class="section-title" style="margin-top:20px;">Approval</div>
  <table class="fields">
    <tr>
      <td class="label">Decided By</td>
      <td class="value">{{ $r->approved_by ?: '—' }}</td>
      <td class="label">Decision Date</td>
      <td class="value">{{ optional($r->decided_at)->format('M d, Y g:i A') }}</td>
    </tr>
    @if($r->decision_note)
    <tr>
      <td class="label">Note</td>
      <td class="value" colspan="3">{{ $r->decision_note }}</td>
    </tr>
    @endif
  </table>
  @endif

  <div class="footer-note">Generated {{ now()->format('M d, Y g:i A') }}</div>

</body>
</html>
