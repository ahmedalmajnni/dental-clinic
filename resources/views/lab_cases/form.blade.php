@extends('layouts.app')
@php($isEdit = $labCase->exists)
@section('title', $isEdit ? 'Edit lab case' : 'New lab case')
@section('content')
<div class="card" style="max-width:560px;">
  <h1>{{ $isEdit ? 'Edit lab case' : 'New lab case' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="patient_id">Patient</label>
    <select id="patient_id" name="patient_id" required>
      <option value="">— choose patient —</option>
      @foreach($patients as $p)
        <option value="{{ $p->id }}" @selected(old('patient_id', $labCase->patient_id) === $p->id)>{{ $p->name }}</option>
      @endforeach
    </select>

    <label for="doctor_id">Supervising doctor</label>
    <select id="doctor_id" name="doctor_id" required>
      <option value="">— choose doctor —</option>
      @foreach($doctors as $d)
        <option value="{{ $d->id }}" @selected(old('doctor_id', $labCase->doctor_id) === $d->id)>{{ $d->name }} ({{ $d->job_title }})</option>
      @endforeach
    </select>

    <label for="type">Type of work</label>
    <input type="text" id="type" name="type" list="type-options" maxlength="60"
           value="{{ old('type', $labCase->type) }}" required placeholder="e.g. Crown">
    <datalist id="type-options">
      @foreach($commonTypes as $t)<option value="{{ $t }}"></option>@endforeach
    </datalist>

    <label for="due_date">Due date</label>
    <input type="date" id="due_date" name="due_date" value="{{ old('due_date', optional($labCase->due_date)->format('Y-m-d')) }}">

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
@endsection
