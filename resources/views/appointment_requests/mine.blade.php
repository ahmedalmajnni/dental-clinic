@extends('layouts.app')
@section('title', 'My requests')
@section('content')
<div class="toolbar">
  <h1>My appointment requests</h1>
  <a href="{{ route('appointment-request.create') }}" class="btn">+ New request</a>
</div>
@if($requests->isEmpty())
  <div class="card"><p class="muted">You haven't made any requests yet.</p></div>
@else
<table>
  <thead><tr><th>Requested</th><th>Doctor</th><th>Specialty</th><th>Preferred</th><th>Status</th><th>Clinic response</th><th></th></tr></thead>
  <tbody>
    @foreach($requests as $r)
      <tr>
        <td>{{ $r->created_at->format('d/m/Y') }}</td>
        <td>{{ $r->doctor->name }}</td>
        <td>{{ $r->doctor?->specialty ?? '—' }}</td>
        <td>{{ $r->preferred_date ? $r->preferred_date->format('d/m/Y') : '—' }}</td>
        <td><span class="badge reqstat-{{ $r->status }}">{{ $r->status }}</span></td>
        <td>
          @if($r->status === 'scheduled' && $r->appointment)
            <strong>Scheduled: {{ $r->appointment->scheduled_at->format('d/m/Y H:i') }}</strong>
            @if($r->response_note)<br><span class="muted">{{ $r->response_note }}</span>@endif
          @elseif($r->status === 'declined')
            <span class="muted">{{ $r->response_note ?: 'Declined.' }}</span>
          @else
            —
          @endif
        </td>
        <td class="actions">
          @if($r->status === 'pending')
            <form method="POST" action="{{ route('my-requests.cancel', $r) }}" onsubmit="return confirm('Cancel this request?');">
              @csrf
              <button class="btn small secondary">Cancel</button>
            </form>
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
