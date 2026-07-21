@extends('layouts.app')
@section('title', 'Process request')
@section('content')
@php($r = $appointmentRequest)
<div class="toolbar">
  <h1>Appointment request</h1>
  <a href="{{ route('requests.index') }}" class="btn secondary">← All requests</a>
</div>

<div class="card">
  <p><strong>Patient:</strong> {{ $r->patient->name }}@if($r->patient->phone) · {{ $r->patient->phone }}@endif</p>
  <p><strong>Doctor:</strong> {{ $r->doctor->name }}</p>
  <p><strong>Branch:</strong> {{ $r->branch->name }}</p>
  <p><strong>Preferred date:</strong> {{ $r->preferred_date ? $r->preferred_date->format('d/m/Y') : '—' }}</p>
  <p><strong>Patient note:</strong> {{ $r->note ?: '—' }}</p>
  <p><strong>Status:</strong> <span class="badge reqstat-{{ $r->status }}">{{ $r->status }}</span></p>
  @if($r->status === 'scheduled' && $r->appointment)
    <p><strong>Scheduled for:</strong> {{ $r->appointment->scheduled_at->format('d/m/Y H:i') }}</p>
  @endif
  @if($r->response_note)<p><strong>Response sent:</strong> {{ $r->response_note }}</p>@endif
</div>

@if($r->status === 'pending')
<div class="card" style="max-width:560px;">
  <h2 style="margin-top:0;font-size:1.1rem;">Schedule the appointment</h2>
  <form method="POST" action="{{ route('requests.schedule', $r) }}">
    @csrf
    <label for="scheduled_at">Date &amp; time</label>
    <input type="datetime-local" id="scheduled_at" name="scheduled_at" required
           value="{{ $r->preferred_date ? $r->preferred_date->format('Y-m-d\T09:00') : '' }}">

    <label for="response_note">Message to patient (optional)</label>
    <textarea id="response_note" name="response_note" placeholder="e.g. Please arrive 10 minutes early"></textarea>

    <div class="actions" style="margin-top:16px;">
      <button type="submit" class="btn">Schedule &amp; confirm</button>
    </div>
  </form>
</div>

<div class="card" style="max-width:560px;">
  <h2 style="margin-top:0;font-size:1.1rem;">Or decline the request</h2>
  <form method="POST" action="{{ route('requests.decline', $r) }}" onsubmit="return confirm('Decline this request?');">
    @csrf
    <label for="decline_note">Reason (shown to the patient)</label>
    <textarea id="decline_note" name="response_note" placeholder="e.g. That doctor is fully booked this month"></textarea>
    <div class="actions" style="margin-top:12px;">
      <button type="submit" class="btn danger">Decline</button>
    </div>
  </form>
</div>
@else
<div class="card"><p class="muted">This request has already been processed.</p></div>
@endif
@endsection
