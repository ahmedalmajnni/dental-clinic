@extends('layouts.app')
@section('title', 'Accounts')
@section('content')
<div class="toolbar">
  <h1>Accounts</h1>
  <div class="actions">
    <a href="{{ route('accounts.requests') }}" class="btn secondary">Staff requests</a>
    <a href="{{ route('accounts.create') }}" class="btn">+ New patient account</a>
  </div>
</div>
<p class="muted">Create <strong>patient</strong> accounts here. <strong>Staff</strong> request access on the sign-up page and appear under <a href="{{ route('accounts.requests') }}">Staff requests</a> for you to approve.</p>
<table>
  <thead><tr><th>Email</th><th>Owner</th><th>Role</th><th>Active</th><th>Last login</th><th></th></tr></thead>
  <tbody>
    @foreach($accounts as $a)
      <tr>
        <td>{{ $a->email }}</td>
        <td>{{ $a->employee->name ?? $a->patient->name ?? '—' }}</td>
        <td><span class="badge">{{ $a->role }}</span></td>
        <td>{{ $a->is_active ? 'Yes' : 'No' }}</td>
        <td>{{ $a->last_login ? $a->last_login->format('d/m/Y H:i') : 'never' }}</td>
        <td class="actions">
          <form method="POST" action="{{ route('accounts.toggle', $a) }}">
            @csrf
            <button class="btn small secondary">{{ $a->is_active ? 'Deactivate' : 'Activate' }}</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endsection
