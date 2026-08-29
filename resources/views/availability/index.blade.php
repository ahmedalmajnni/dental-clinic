@extends('layouts.app')
@section('title', 'Doctor availability')
@section('content')
@php
  $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
@endphp
<div class="toolbar">
  <h1>Availability</h1>
  <a href="{{ route('dashboard') }}" class="btn secondary">← Dashboard</a>
</div>
<p class="muted">Appointments can only be booked inside these weekly hours. A doctor with no hours set cannot be scheduled at all.</p>

@if($doctors->isEmpty())
  <div class="card"><p class="muted">No doctors yet.</p></div>
@else
<table>
  <thead><tr><th>Doctor</th><th>Branch</th><th>Weekly hours</th><th>Exceptions</th><th></th></tr></thead>
  <tbody>
    @foreach($doctors as $d)
      @php
        $byDay = $d->availability->sortBy('start_time')->groupBy('weekday')->sortKeys();
        $exceptions = $d->timeOff->filter(fn ($t) => $t->on_date && $t->on_date->gte(today()))->count();
      @endphp
      <tr>
        <td>{{ $d->name }}</td>
        <td>{{ $d->branch->name ?? '—' }}</td>
        <td>
          @if($byDay->isEmpty())
            <span class="badge kind-off">Not bookable — no hours set</span>
          @else
            <div class="avail-summary">
              @foreach($byDay as $weekday => $rows)
                <div>
                  <span class="day">{{ $dayNames[$weekday] }}</span>
                  {{ $rows->map(fn ($r) => substr($r->start_time, 0, 5) . '–' . substr($r->end_time, 0, 5))->implode(', ') }}
                </div>
              @endforeach
            </div>
          @endif
        </td>
        <td>{{ $exceptions ?: '—' }}</td>
        <td class="actions">
          <a href="{{ route('availability.edit', $d) }}" class="btn small secondary">Edit hours</a>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
