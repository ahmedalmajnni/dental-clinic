@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="auth-split">
  <div class="auth-panel">
    <div class="auth-mark"><img src="{{ asset('images/logo.svg') }}" alt=""> <span>Dental Clinic</span></div>
    <div class="auth-quote">
      <h2>One record, from the front desk to the chair.</h2>
      <p>Appointments, treatments and billing, connected for patients, dentists and admins.</p>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <div class="eyebrow">Welcome back</div>
      <h1>Log in</h1>
    <form method="POST" action="{{ route('login') }}">
      @csrf
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <div style="margin-top:16px;">
        <button type="submit" class="btn solid" style="width:100%;">Log in</button>
      </div>
    </form>
    <p class="muted" style="margin-top:16px;">
      New patient? <a href="{{ route('signup') }}">Create an account</a>
    </p>
    </div>
  </div>
</div>
@endsection
