@extends('layouts.app')
@section('title', 'Create patient account')
@section('content')
<div class="auth-wrap">
  <div class="card">
    <h1>Create patient account</h1>
    <form method="POST" action="{{ route('signup') }}">
      @csrf
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required>
      <label for="phone">Phone (optional)</label>
      <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
      <label for="dob">Date of birth (optional)</label>
      <input type="date" id="dob" name="dob" value="{{ old('dob') }}">
      <label for="password">Password (min 6 characters)</label>
      <input type="password" id="password" name="password" required>
      <div style="margin-top:16px;">
        <button type="submit" class="btn" style="width:100%;">Sign up</button>
      </div>
    </form>
    <p class="muted" style="margin-top:16px;">
      Already have an account? <a href="{{ route('login') }}">Log in</a>
    </p>
  </div>
</div>
@endsection
