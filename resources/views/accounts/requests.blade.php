@extends('layouts.app')
@section('title', 'Staff requests')
@section('content')
<div class="toolbar">
  <h1>Staff access requests</h1>
  <div class="actions">
    <a href="{{ route('accounts.index') }}" class="btn secondary">← All accounts</a>
    <a href="{{ route('accounts.create-staff') }}" class="btn">+ New staff account</a>
  </div>
</div>
<p class="muted">
  People who requested staff access. They cannot log in until you approve them.
  Hired someone already? <a href="{{ route('accounts.create-staff') }}">Create their account directly</a> —
  it is active straight away, with nothing to approve.
</p>

@if($pending->isEmpty())
  <div class="card"><p class="muted">No pending staff requests.</p></div>
@else
<table>
  <thead><tr><th>Requested</th><th>Name</th><th>Email</th><th>Job title</th><th>Branch</th><th></th></tr></thead>
  <tbody>
    @foreach($pending as $a)
      <tr>
        <td>{{ $a->created_at->format('d/m/Y') }}</td>
        <td>{{ $a->employee->name ?? '—' }}</td>
        <td>{{ $a->email }}</td>
        <td><span class="badge">{{ $a->employee->job_title ?? '—' }}</span></td>
        <td>{{ $a->employee->branch->name ?? '—' }}</td>
        <td class="actions">
          <form method="POST" action="{{ route('accounts.approve', $a) }}">
            @csrf
            <button class="btn small">Accept</button>
          </form>
          <form method="POST" action="{{ route('accounts.reject', $a) }}" onsubmit="return confirm('Reject and remove this request?');">
            @csrf
            <button class="btn small danger">Reject</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
