@extends('layouts.app')
@php($isEdit = $patient->exists)
@section('title', $isEdit ? 'Edit patient' : 'New patient')
@section('content')
<div class="card" style="max-width:520px;">
  <h1>{{ $isEdit ? 'Edit patient' : 'New patient' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $patient->name) }}" required autofocus>

    <label for="dob">Date of birth</label>
    <input type="date" id="dob" name="dob" value="{{ old('dob', optional($patient->dob)->format('Y-m-d')) }}">

    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $patient->phone) }}">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="{{ old('email', $patient->email) }}">

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('patients.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
