@extends('layouts.app')
@php($isEdit = $treatment->exists)
@section('title', $isEdit ? 'Edit treatment' : 'New treatment')
@section('content')
<div class="card" style="max-width:560px;">
  <h1>{{ $isEdit ? 'Edit treatment' : 'New treatment' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    @if($isEdit)
      <p class="muted">Editing an existing treatment. Its patient stays the same.</p>
    @else
      <label for="appointment_id">Appointment</label>
      <select id="appointment_id" name="appointment_id" required>
        <option value="">— choose the visit —</option>
        @foreach($appointments as $a)
          <option value="{{ $a->id }}">{{ $a->patient->name }} — {{ $a->scheduled_at->format('d/m/Y H:i') }}</option>
        @endforeach
      </select>
      <p class="muted">The patient is taken from this appointment.</p>
    @endif

    <label for="procedure">Procedure</label>
    <input type="text" id="procedure" name="procedure" value="{{ old('procedure', $treatment->procedure) }}" required>

    <label for="cost">Cost</label>
    <input type="number" id="cost" name="cost" step="0.01" min="0" value="{{ old('cost', $treatment->cost ?? '0.00') }}">

    <label for="status">Status</label>
    <select id="status" name="status">
      @foreach($statuses as $s)
        <option value="{{ $s }}" @selected(old('status', $treatment->status) === $s)>{{ $s }}</option>
      @endforeach
    </select>

    <p class="muted" style="margin-top:12px;">💡 Saving a treatment automatically adds it to the patient's invoice as a billing line.</p>

    <div class="actions" style="margin-top:8px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('treatments.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
