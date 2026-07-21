@extends('layouts.app')
@section('title', 'Appointments')
@section('content')
<div class="toolbar">
  <h1>Appointments</h1>
  <a href="{{ route('appointments.create') }}" class="btn">+ New appointment</a>
</div>
@if($appointments->isEmpty())
  <div class="card"><p class="muted">No appointments yet.</p></div>
@else
<table>
  <thead><tr><th>When</th><th>Patient</th><th>Doctor</th><th>Branch</th><th>Status</th><th></th></tr></thead>
  <tbody>
    @foreach($appointments as $a)
      <tr>
        <td>{{ $a->scheduled_at->format('d/m/Y H:i') }}</td>
        <td>{{ $a->patient->name }}</td>
        <td>{{ $a->doctor->name }}</td>
        <td>{{ $a->branch->name }}</td>
        <td><span class="badge">{{ $a->status }}</span></td>
        <td class="actions">
          <a href="{{ route('appointments.edit', $a) }}" class="btn small secondary">Edit</a>
          <form method="POST" action="{{ route('appointments.destroy', $a) }}" onsubmit="return confirm('Delete this appointment?');">
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
