@extends('layouts.app')
@section('title', 'Accounts')
@section('content')
<div class="toolbar">
  <h1>Accounts</h1>
  <div class="actions">
    <a href="{{ route('accounts.requests') }}" class="btn secondary">Staff requests</a>
    @if($type === 'staff')
      <a href="{{ route('accounts.create-staff') }}" class="btn">+ New staff account</a>
    @else
      <a href="{{ route('accounts.create') }}" class="btn">+ New patient account</a>
    @endif
  </div>
</div>

<div class="tabs">
  <a class="tab {{ $type === 'staff' ? 'on' : '' }}" href="{{ route('accounts.index', ['type' => 'staff']) }}">
    Staff <span class="count">{{ $staffCount }}</span>
  </a>
  <a class="tab {{ $type === 'patient' ? 'on' : '' }}" href="{{ route('accounts.index', ['type' => 'patient']) }}">
    Patients <span class="count">{{ $patientCount }}</span>
  </a>
</div>

@if($type === 'staff')
  <p class="muted">Doctors, reception and lab staff request access on the sign-up page and appear under
    <a href="{{ route('accounts.requests') }}">Staff requests</a> for you to approve.</p>
@else
  <p class="muted">Patient logins. Create one with <strong>+ New patient account</strong>, or let patients sign up themselves.</p>
@endif

@if($accounts->isEmpty())
  <div class="card"><p class="muted">No {{ $type === 'staff' ? 'staff' : 'patient' }} accounts yet.</p></div>
@else
<table>
  <thead>
    <tr>
      <th>Email</th>
      <th>Name</th>
      @if($type === 'staff')
        <th>Job title</th>
        <th>Branch</th>
      @else
        <th>Phone</th>
        <th>Date of birth</th>
      @endif
      <th>Active</th>
      <th>Last login</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    @foreach($accounts as $a)
      <tr>
        <td>{{ $a->email }}</td>
        @if($type === 'staff')
          <td>{{ $a->employee?->name ?? '—' }}</td>
          <td><span class="badge job">{{ str_replace('_', ' ', $a->employee?->job_title ?? $a->role) }}</span></td>
          <td>{{ $a->employee?->branch?->name ?? '—' }}</td>
        @else
          <td>{{ $a->patient?->name ?? '—' }}</td>
          <td>{{ $a->patient?->phone ?: '—' }}</td>
          <td>{{ $a->patient?->dob ? $a->patient->dob->format('d/m/Y') : '—' }}</td>
        @endif
        <td>{{ $a->is_active ? 'Yes' : 'No' }}</td>
        <td>{{ $a->last_login ? $a->last_login->format('d/m/Y H:i') : 'never' }}</td>
        <td class="actions">
          <a class="btn small secondary edit-action" href="{{ route('accounts.edit', $a) }}">Edit</a>
          <form method="POST" action="{{ route('accounts.toggle', $a) }}">
            @csrf
            <button class="btn small {{ $a->is_active ? 'danger' : 'edit-action' }}">{{ $a->is_active ? 'Deactivate' : 'Activate' }}</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
