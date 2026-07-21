@extends('layouts.app')
@php($isEdit = $appointment->exists)
@section('title', $isEdit ? 'Edit appointment' : 'New appointment')
@section('content')
<div class="card" style="max-width:560px;">
  <h1>{{ $isEdit ? 'Edit appointment' : 'New appointment' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="patient_id">Patient</label>
    <select id="patient_id" name="patient_id" required>
      <option value="">— choose patient —</option>
      @foreach($patients as $p)
        <option value="{{ $p->id }}" @selected(old('patient_id', $appointment->patient_id) === $p->id)>{{ $p->name }}</option>
      @endforeach
    </select>

    <label for="doctor_id">Doctor</label>
    <select id="doctor_id" name="doctor_id" required>
      <option value="">— choose doctor —</option>
      @foreach($doctors as $d)
        <option value="{{ $d->id }}" @selected(old('doctor_id', $appointment->doctor_id) === $d->id)>{{ $d->name }} ({{ $d->job_title }})</option>
      @endforeach
    </select>

    <label for="branch_id">Branch</label>
    <select id="branch_id" name="branch_id" required>
      <option value="">— choose branch —</option>
      @foreach($branches as $b)
        <option value="{{ $b->id }}" @selected(old('branch_id', $appointment->branch_id) === $b->id)>{{ $b->name }}</option>
      @endforeach
    </select>

    <label for="scheduled_at">Date &amp; time</label>
    <input type="datetime-local" id="scheduled_at" name="scheduled_at" required
           value="{{ old('scheduled_at', optional($appointment->scheduled_at)->format('Y-m-d\TH:i')) }}">

    <label for="status">Status</label>
    <select id="status" name="status">
      @foreach($statuses as $s)
        <option value="{{ $s }}" @selected(old('status', $appointment->status) === $s)>{{ $s }}</option>
      @endforeach
    </select>

    <hr style="margin:22px 0; border:none; border-top:1px solid var(--border);">
    <h2 style="font-size:1.05rem; margin:0 0 4px;">Clinical notes</h2>
    <p class="muted" style="margin-top:0;">Fill these in after the visit — leave blank when booking.</p>

    <label for="diagnosis">Diagnosis</label>
    <input type="text" id="diagnosis" name="diagnosis" value="{{ old('diagnosis', $report->diagnosis ?? '') }}">

    <label for="notes">Notes</label>
    <textarea id="notes" name="notes" placeholder="Treatment given, advice, follow-up…">{{ old('notes', $report->notes ?? '') }}</textarea>

    <label for="next_visit">Next visit</label>
    <input type="date" id="next_visit" name="next_visit"
           value="{{ old('next_visit', optional($report->next_visit ?? null)->format('Y-m-d')) }}">

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('appointments.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
