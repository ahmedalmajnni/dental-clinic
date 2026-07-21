@extends('layouts.app')
@section('title', 'Employees')
@section('content')
<div class="toolbar">
  <h1>Employees</h1>
  <a href="{{ route('accounts.requests') }}" class="btn secondary">Staff requests</a>
</div>
<p class="muted">Employees join by requesting access on the sign-up page; approve them under <a href="{{ route('accounts.requests') }}">Staff requests</a>. You can edit or remove existing employees below.</p>
@if($employees->isEmpty())
  <div class="card"><p class="muted">No employees yet.</p></div>
@else
<table>
  <thead><tr><th>Name</th><th>Job title</th><th>Branch</th><th>Phone</th><th></th></tr></thead>
  <tbody>
    @foreach($employees as $e)
      <tr>
        <td>{{ $e->name }}</td>
        <td><span class="badge">{{ $e->job_title }}</span></td>
        <td>{{ $e->branch->name }}</td>
        <td>{{ $e->phone ?: '—' }}</td>
        <td class="actions">
          <a href="{{ route('employees.edit', $e) }}" class="btn small secondary">Edit</a>
          <form method="POST" action="{{ route('employees.destroy', $e) }}" onsubmit="return confirm('Delete this employee?');">
            @csrf @method('DELETE')
            <button class="btn small danger">Delete</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
