@extends('layouts.app')
@section('title', 'New patient account')
@section('content')
<div class="card" style="max-width:520px;">
  <h1>New patient account</h1>
  <p class="muted">Create a patient record together with their login. (Staff join by requesting access on the sign-up page, then being approved.)</p>

  <form method="POST" action="{{ route('accounts.store') }}">
    @csrf
    <label for="pat_name">Name</label>
    <input type="text" id="pat_name" name="pat_name" value="{{ old('pat_name') }}" required autofocus>

    <label for="dob">Date of birth</label>
    <input type="date" id="dob" name="dob" value="{{ old('dob') }}">

    <label for="pat_phone">Phone</label>
    <input type="text" id="pat_phone" name="pat_phone" value="{{ old('pat_phone') }}">

    <label for="email">Login email</label>
    <input type="email" id="email" name="email" value="{{ old('email') }}" required>

    <label for="password">Password (min 6 characters)</label>
    <input type="password" id="password" name="password" required>

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Create patient account</button>
      <a href="{{ route('accounts.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
