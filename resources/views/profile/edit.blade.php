@extends('layouts.app')
@section('title', 'My account')
@section('content')
@php
  $isPatient = $account->role === 'patient';
  $job = $account->employee?->job_title;
  $statusLabel = $isPatient ? 'Patient' : ucfirst(str_replace('_', ' ', $job ?: $account->role));
@endphp

<div class="toolbar">
  <h1>My account</h1>
</div>

<div class="grid-2">
  <div>
    <div class="panel">
      <div class="panel-head"><h2>Your details</h2></div>
      <div class="panel-body">
        <form method="POST" action="{{ route('profile.update') }}">
          @csrf
          @method('PUT')

          <label for="name">Full name</label>
          <input type="text" id="name" name="name" required
                 value="{{ old('name', $owner->name ?? '') }}">

          <label for="email">Login email</label>
          <input type="email" id="email" name="email" required
                 value="{{ old('email', $account->email) }}">
          <p class="muted">This is the address you sign in with.</p>

          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone"
                 value="{{ old('phone', $owner->phone ?? '') }}">

          @if($isPatient)
            <label for="dob">Date of birth</label>
            <input type="date" id="dob" name="dob"
                   value="{{ old('dob', optional($owner?->dob)->format('Y-m-d')) }}">
          @endif

          <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Save details</button>
          </div>
        </form>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Change password</h2></div>
      <div class="panel-body">
        <form method="POST" action="{{ route('profile.password') }}">
          @csrf
          @method('PUT')

          <label for="current_password">Current password</label>
          <input type="password" id="current_password" name="current_password" required autocomplete="current-password">

          <label for="password">New password</label>
          <input type="password" id="password" name="password" required autocomplete="new-password">
          <p class="muted">At least 6 characters.</p>

          <label for="password_confirmation">Repeat new password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">

          <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Change password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div>
    <div class="panel">
      <div class="panel-head"><h2>Status</h2></div>
      <div class="panel-body">
        <p style="margin-top:0;">
          <span class="badge job">{{ $statusLabel }}</span>
          <span class="badge {{ $account->is_active ? 'status-paid' : 'status-open' }}">
            {{ $account->is_active ? 'Active' : 'Inactive' }}
          </span>
        </p>

        <table>
          <tbody>
            <tr><th>Role</th><td>{{ $account->role }}</td></tr>
            @if(! $isPatient)
              <tr><th>Job title</th><td>{{ str_replace('_', ' ', $job ?: '—') }}</td></tr>
              <tr><th>Specialty</th><td>{{ $account->employee?->specialty ?? '—' }}</td></tr>
            @endif
            <tr><th>Member since</th><td>{{ $account->created_at?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><th>Last login</th><td>{{ $account->last_login?->format('d/m/Y H:i') ?? 'never' }}</td></tr>
          </tbody>
        </table>

        {{-- These are set by an administrator, so say so rather than showing a
             disabled field the person will try to click. --}}
        <p class="muted" style="margin-bottom:0;">
          @if($isPatient)
            Your role is fixed. Contact the clinic if anything here is wrong.
          @else
            Job title, specialty and active status are managed by an administrator.
          @endif
        </p>
      </div>
    </div>

    @if($account->role === 'admin' || optional($account->employee)->job_title === 'doctor')
      <div class="panel">
        <div class="panel-head"><h2>Shortcuts</h2></div>
        <div class="qa-list">
          <a class="qa" href="{{ route('availability.index') }}"><span class="ico">🕒</span> My working hours</a>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
