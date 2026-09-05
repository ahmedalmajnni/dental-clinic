@extends('layouts.app')
@section('title', 'Edit account')
@section('content')
@php
  $isPatient = $account->role === 'patient';
  $job = $account->employee?->job_title;
  $statusLabel = $isPatient ? 'Patient' : ucfirst(str_replace('_', ' ', $job ?: $account->role));
  $backType = $isPatient ? 'patient' : 'staff';
@endphp

<div class="toolbar">
  <h1>Edit account</h1>
  <a href="{{ route('accounts.index', ['type' => $backType]) }}" class="btn secondary">← All accounts</a>
</div>

<div class="grid-2">
  <div>
    <div class="panel">
      <div class="panel-head"><h2>{{ $owner->name ?? $account->email }}</h2></div>
      <div class="panel-body">
        <form method="POST" action="{{ route('accounts.update', $account) }}">
          @csrf
          @method('PUT')

          <label for="name">Full name</label>
          <input type="text" id="name" name="name" required
                 value="{{ old('name', $owner->name ?? '') }}">

          <label for="email">Login email</label>
          <input type="email" id="email" name="email" required
                 value="{{ old('email', $account->email) }}">

          <label for="phone">Phone</label>
          <input type="text" id="phone" name="phone"
                 value="{{ old('phone', $owner->phone ?? '') }}">

          @if($isPatient)
            <label for="dob">Date of birth</label>
            <input type="date" id="dob" name="dob"
                   value="{{ old('dob', optional($owner?->dob)->format('Y-m-d')) }}">
          @endif

          <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn">Save changes</button>
            <a href="{{ route('accounts.index', ['type' => $backType]) }}" class="btn secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Reset password</h2></div>
      <div class="panel-body">
        <p class="muted" style="margin-top:0;">
          Sets a new password immediately. You will need to tell them what it is —
          it is stored hashed and cannot be read back.
        </p>
        <form method="POST" action="{{ route('accounts.password', $account) }}">
          @csrf
          @method('PUT')

          <label for="password">New password</label>
          <input type="password" id="password" name="password" required autocomplete="new-password">
          <p class="muted">At least 6 characters.</p>

          <label for="password_confirmation">Repeat new password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">

          <div class="actions" style="margin-top:16px;">
            <button type="submit" class="btn danger">Reset password</button>
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
            <tr><th>Created</th><td>{{ $account->created_at?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><th>Last login</th><td>{{ $account->last_login?->format('d/m/Y H:i') ?? 'never' }}</td></tr>
          </tbody>
        </table>

        {{-- Each of these rules lives in one place; send the admin there rather
             than duplicating the guards on this screen. --}}
        <div class="actions" style="margin-top:12px;">
          @if($account->id !== auth()->id())
            <form method="POST" action="{{ route('accounts.toggle', $account) }}">
              @csrf
              <button class="btn small {{ $account->is_active ? 'danger' : 'edit-action' }}">{{ $account->is_active ? 'Deactivate' : 'Activate' }}</button>
            </form>
          @endif
          @if($account->employee)
            <a class="btn small secondary" href="{{ route('employees.edit', $account->employee) }}">Job title &amp; specialty</a>
          @endif
        </div>
        @if($account->id === auth()->id())
          <p class="muted" style="margin-bottom:0;">This is your own account — you cannot deactivate it here.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
