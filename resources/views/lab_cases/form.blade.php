@extends('layouts.app')
@php($isEdit = $labCase->exists)
@section('title', $isEdit ? 'Edit lab case' : 'New lab case')
@section('content')
<div class="card" style="max-width:560px;">
  <h1>{{ $isEdit ? 'Edit lab case' : 'New lab case' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="appointment_id">Visit</label>
    <select id="appointment_id" name="appointment_id" required>
      <option value="">— choose a visit —</option>
      @foreach($appointments as $a)
        <option value="{{ $a->id }}" data-due-date="{{ $a->report->next_visit->copy()->subDay()->format('Y-m-d') }}">P: {{ $a->patient->name }} __ D: {{ $a->doctor->name }} __ {{ $a->scheduled_at->format('d/m/Y') }}</option>
      @endforeach
    </select>
    <p class="muted">Only completed appointments with a next visit are available.</p>

    <label for="type">Type of work</label>
    <input type="text" id="type" name="type" list="type-options" maxlength="60"
           value="{{ old('type', $labCase->type) }}" required placeholder="e.g. Crown">
    <datalist id="type-options">
      @foreach($commonTypes as $t)<option value="{{ $t }}"></option>@endforeach
    </datalist>

    <label for="due_date">Due date</label>
    <input type="date" id="due_date" name="due_date" value="{{ old('due_date', optional($labCase->due_date)->format('Y-m-d')) }}">
    <p class="muted">Automatically set to one day before the selected visit's next visit. You can change it.</p>

    <label for="status">Status</label>
    <select id="status" name="status">
      @foreach($statuses as $s)
        <option value="{{ $s }}" @selected(old('status', $labCase->status) === $s)>{{ str_replace('_', ' ', $s) }}</option>
      @endforeach
    </select>

    <label for="cost">Cost</label>
    <input type="number" id="cost" name="cost" step="0.01" min="0" value="{{ old('cost', $labCase->cost ?? '0.00') }}">

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('lab-cases.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
<script>
  var visitField = document.getElementById('appointment_id');
  var dueDateField = document.getElementById('due_date');

  function updateDueDate() {
    var selected = visitField.options[visitField.selectedIndex];
    dueDateField.value = selected ? (selected.dataset.dueDate || '') : '';
  }

  visitField.addEventListener('change', updateDueDate);
  updateDueDate();
</script>
@endsection
