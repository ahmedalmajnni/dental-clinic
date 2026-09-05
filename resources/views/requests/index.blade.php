@extends('layouts.app')
@section('title', 'Appointment requests')
@section('content')
<div class="toolbar">
  <h1>Appointment requests</h1>
</div>
@if($requests->isEmpty())
  <div class="card"><p class="muted">No pending appointment requests.</p></div>
@else
<table>
  <thead><tr><th>Requested</th><th>Patient</th><th>Doctor</th><th>Specialty</th><th>Preferred</th><th>Status</th><th></th></tr></thead>
  <tbody>
    @foreach($requests as $r)
      <tr>
        <td>{{ $r->created_at->format('d/m/Y') }}</td>
        <td>{{ $r->patient->name }}</td>
        <td>{{ $r->doctor->name }}</td>
        <td>{{ $r->doctor?->specialty ?? '—' }}</td>
        <td>{{ $r->preferred_date ? $r->preferred_date->format('d/m/Y') : '—' }}</td>
        <td><span class="badge reqstat-{{ $r->status }}">{{ $r->status }}</span></td>
        <td class="actions">
          @if($r->status === 'pending')
            <a href="{{ route('requests.process', $r) }}" class="btn small">Process</a>
          @else
            <a href="{{ route('requests.process', $r) }}" class="btn small secondary">View</a>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
