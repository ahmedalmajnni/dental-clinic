@extends('layouts.app')
@section('title', 'Appointments')
@section('content')
<div class="toolbar">
  <h1>Appointments</h1>
  <a href="{{ route('appointments.create') }}" class="btn">+ New appointment</a>
</div>
<div class="tabs">
  <a class="tab {{ $status === 'booked' ? 'on' : '' }}" href="{{ route('appointments.index', ['status' => 'booked']) }}">
    Booked <span class="count">{{ $bookedCount }}</span>
  </a>
  <a class="tab {{ $status === 'completed' ? 'on' : '' }}" href="{{ route('appointments.index', ['status' => 'completed']) }}">
    Completed <span class="count">{{ $completedCount }}</span>
  </a>
  <a class="tab {{ $status === 'cancelled' ? 'on' : '' }}" href="{{ route('appointments.index', ['status' => 'cancelled']) }}">
    Archived <span class="count">{{ $cancelledCount }}</span>
  </a>
</div>
@if($appointments->isEmpty())
  <div class="card"><p class="muted">No {{ $status === 'cancelled' ? 'archived' : $status }} appointments.</p></div>
@else
<table>
  <thead><tr><th>When</th><th>Patient</th>@if($showDoctor)<th>Doctor</th>@endif<th>Specialty</th><th>Status</th><th></th></tr></thead>
  <tbody>
    @foreach($appointments as $a)
      <tr>
        <td>{{ $a->scheduled_at->format('d/m/Y H:i') }}</td>
        <td>{{ $a->patient->name }}</td>
        @if($showDoctor)<td>{{ $a->doctor->name }}</td>@endif
        <td>{{ $a->doctor?->specialty ?? '—' }}</td>
        <td><span class="badge">{{ $a->status }}</span></td>
        <td class="actions">
          <a href="{{ route('appointments.edit', $a) }}" class="btn small secondary edit-action">Edit</a>
          @if($status !== 'cancelled')
          <form method="POST" action="{{ route('appointments.destroy', $a) }}" onsubmit="return confirm('Archive this appointment?');">
            @csrf @method('DELETE')
            <button class="btn small danger">Archive</button>
          </form>
          @else
          <form method="POST" action="{{ route('appointments.force-delete', $a) }}" onsubmit="return confirm('Permanently delete this appointment and its clinical history? This cannot be undone.');">
            @csrf @method('DELETE')
            <button class="btn small danger">Delete permanently</button>
          </form>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
