@extends('layouts.app')
@php($isEdit = $employee->exists)
@section('title', $isEdit ? 'Edit employee' : 'New employee')
@section('content')
<div class="card" style="max-width:520px;">
  <h1>{{ $isEdit ? 'Edit employee' : 'New employee' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $employee->name) }}" required autofocus>

    <label for="job_title">Job title</label>
    <select id="job_title" name="job_title" required>
      @foreach($jobTitles as $j)
        <option value="{{ $j }}" @selected(old('job_title', $employee->job_title) === $j)>{{ $j }}</option>
      @endforeach
    </select>

    <label for="specialty">Specialty</label>
    <select id="specialty" name="specialty">
      <option value="">— choose specialty —</option>
      @foreach($specialties as $specialty)
        <option value="{{ $specialty->name }}" @selected(old('specialty', $employee->specialty) === $specialty->name)>{{ $specialty->name }}</option>
      @endforeach
    </select>

    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}">

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('employees.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
  @if($specialties->isEmpty())
    <p class="muted" style="margin-top:12px;">You need at least one specialty first. <a href="{{ route('specialties.create') }}">Create a specialty</a>.</p>
  @endif
</div>
@endsection
