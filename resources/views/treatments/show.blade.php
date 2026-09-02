@extends('layouts.app')
@section('title', 'Treatment report')
@section('content')
<div class="toolbar">
  <h1>Case report</h1>
  <a href="{{ route('treatments.index') }}" class="btn secondary">← Back to treatments</a>
</div>

<div class="card">
  <div style="display:grid; gap:18px;">
    <div>
      <div class="muted">Patient</div>
      <div style="font-size:1.35rem; font-weight:600;">{{ $treatment->patient->name }}</div>
    </div>

    <div style="display:flex; gap:24px; flex-wrap:wrap; align-items:center;">
      <div><div class="muted">Procedure</div><strong>{{ $treatment->procedure }}</strong></div>
      <div><div class="muted">Status</div><span class="badge">{{ $treatment->status }}</span></div>
      <div><div class="muted">Cost</div><strong>${{ number_format($treatment->cost, 2) }}</strong></div>
      <div><div class="muted">Visit date</div><strong>{{ optional($appointment?->scheduled_at)->format('d/m/Y H:i') ?? '—' }}</strong></div>
    </div>
  </div>
</div>

<div class="card">
  <h2 style="margin-top:0; font-size:1.1rem;">Clinical notes</h2>

  @if($report)
    <div style="display:grid; gap:16px;">
      <div>
        <div class="muted">Diagnosis</div>
        <p>{{ $report->diagnosis ?: 'No diagnosis recorded.' }}</p>
      </div>

      <div>
        <div class="muted">What was done</div>
        <p>{{ $report->notes ?: 'No treatment notes recorded yet.' }}</p>
      </div>

      <div>
        <div class="muted">Next visit</div>
        <p>{{ optional($report->next_visit)->format('d/m/Y') ?? 'No follow-up date set.' }}</p>
      </div>
    </div>
  @else
    <p class="muted">No report is linked to this treatment yet. Add notes on the appointment for this case.</p>
  @endif
</div>

<div class="card">
  <h2 style="margin-top:0; font-size:1.1rem;">Visit details</h2>
  <dl style="margin:0; display:grid; gap:10px;">
    <div><dt class="muted" style="display:inline-block; min-width:120px;">Doctor</dt><dd style="display:inline; margin:0;">{{ $appointment?->doctor?->name ?? '—' }}</dd></div>
    <div><dt class="muted" style="display:inline-block; min-width:120px;">Branch</dt><dd style="display:inline; margin:0;">{{ $appointment?->branch?->name ?? '—' }}</dd></div>
  </dl>
</div>
@endsection
