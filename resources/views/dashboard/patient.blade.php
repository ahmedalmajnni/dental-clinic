@extends('layouts.app')
@section('title', 'My dashboard')
@section('content')
<h1>Welcome, {{ auth()->user()->name }}</h1>

<div class="card">
  <div class="toolbar" style="margin-bottom:12px;">
    <h2 style="margin:0;font-size:1.15rem;">My appointment requests</h2>
    <a href="{{ route('appointment-request.create') }}" class="btn">+ Request an appointment</a>
  </div>
  @if($requests->isEmpty())
    <p class="muted">You have no requests yet. Click "Request an appointment" to ask for a visit.</p>
  @else
    <table>
      <thead><tr><th>Requested</th><th>Doctor</th><th>Branch</th><th>Status</th><th>Clinic response</th></tr></thead>
      <tbody>
        @foreach($requests as $r)
          <tr>
            <td>{{ $r->created_at->format('d/m/Y') }}</td>
            <td>{{ $r->doctor->name }}</td>
            <td>{{ $r->branch->name }}</td>
            <td><span class="badge reqstat-{{ $r->status }}">{{ $r->status }}</span></td>
            <td>
              @if($r->status === 'scheduled' && $r->appointment)
                <strong>{{ $r->appointment->scheduled_at->format('d/m/Y H:i') }}</strong>
                @if($r->response_note)<br><span class="muted">{{ $r->response_note }}</span>@endif
              @elseif($r->status === 'declined')
                <span class="muted">{{ $r->response_note ?: 'Declined.' }}</span>
              @else
                —
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <p class="muted" style="margin-top:10px;"><a href="{{ route('my-requests') }}">See all my requests →</a></p>
  @endif
</div>

<div class="card">
  <h2 style="margin-top:0;font-size:1.15rem;">My bills</h2>
  @if($invoices->isEmpty())
    <p class="muted">You have no invoices.</p>
  @else
    <p>Total outstanding balance: <strong style="color:#dc2626;">${{ number_format($outstanding, 2) }}</strong></p>
    <table>
      <thead><tr><th>Date</th><th>Total</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($invoices as $i)
          <tr>
            <td>{{ $i->created_at->format('d/m/Y') }}</td>
            <td>${{ number_format($i->total, 2) }}</td>
            <td>${{ number_format($i->balance, 2) }}</td>
            <td><span class="badge status-{{ $i->status }}">{{ $i->status }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card">
  <h2 style="margin-top:0;font-size:1.15rem;">My appointments</h2>
  @if($appointments->isEmpty())
    <p class="muted">You have no appointments yet.</p>
  @else
    <table>
      <thead><tr><th>When</th><th>Doctor</th><th>Branch</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($appointments as $a)
          <tr>
            <td>{{ $a->scheduled_at->format('d/m/Y H:i') }}</td>
            <td>{{ $a->doctor->name }}</td>
            <td>{{ $a->branch->name }}</td>
            <td><span class="badge">{{ $a->status }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card">
  <h2 style="margin-top:0;font-size:1.15rem;">My treatments</h2>
  @if($treatments->isEmpty())
    <p class="muted">No treatments recorded yet.</p>
  @else
    <table>
      <thead><tr><th>Procedure</th><th>Cost</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        @foreach($treatments as $t)
          <tr>
            <td>{{ $t->procedure }}</td>
            <td>${{ number_format($t->cost, 2) }}</td>
            <td><span class="badge">{{ $t->status }}</span></td>
            <td>{{ $t->created_at->format('d/m/Y') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
