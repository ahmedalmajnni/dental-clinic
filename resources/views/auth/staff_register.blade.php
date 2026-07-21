@extends('layouts.app')
@section('title', 'Request staff access')
@section('content')
<div class="auth-wrap">
  <div class="card">
    <h1>Request staff access</h1>
    <p class="muted">Submit your details. A manager will review your request — you'll be able to log in once it's approved.</p>
    @if($branches->isEmpty())
      <p class="muted">Registration isn't available yet. Please contact the clinic.</p>
    @else
    <form method="POST" action="{{ route('staff-register.store') }}">
      @csrf
      <label for="name">Full name</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>

      <label for="branch_id">Branch</label>
      <select id="branch_id" name="branch_id" required>
        <option value="">— choose branch —</option>
        @foreach($branches as $b)
          <option value="{{ $b->id }}" {{ old('branch_id') === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
        @endforeach
      </select>

      <label for="job_title">Job title</label>
      <select id="job_title" name="job_title" required>
        @foreach($jobTitles as $j)
          <option value="{{ $j }}" {{ old('job_title') === $j ? 'selected' : '' }}>{{ $j }}</option>
        @endforeach
      </select>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required>

      <label for="password">Password (min 6 characters)</label>
      <input type="password" id="password" name="password" required>

      <div style="margin-top:16px;">
        <button type="submit" class="btn" style="width:100%;">Submit request</button>
      </div>
    </form>
    @endif
    <p class="muted" style="margin-top:16px;"><a href="{{ route('login') }}">← Back to log in</a></p>
  </div>
</div>
@endsection
