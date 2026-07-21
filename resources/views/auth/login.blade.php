@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="auth-wrap">
  <div class="card">
    <h1>🦷 Log in</h1>
    <form method="POST" action="{{ route('login') }}">
      @csrf
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus>
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
      <div style="margin-top:16px;">
        <button type="submit" class="btn" style="width:100%;">Log in</button>
      </div>
    </form>
    <p class="muted" style="margin-top:16px;">
      New patient? <a href="{{ route('signup') }}">Create an account</a><br>
      Staff member? <a href="{{ route('staff-register.create') }}">Request access</a>
    </p>
  </div>
</div>
@endsection
