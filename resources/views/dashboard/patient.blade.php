@extends('layouts.app')
@section('title', 'My clinic')
@section('content')
@php
  // Patients read their own history here, so the stored status words are shown
  // in plain language rather than the clinic's internal wording.
  $VISIT = ['booked' => 'Upcoming', 'completed' => 'Done', 'cancelled' => 'Cancelled', 'no_show' => 'Missed'];
  $REQ   = ['pending' => 'Waiting', 'scheduled' => 'Confirmed', 'declined' => 'Not available', 'cancelled' => 'Cancelled'];
  $BILL  = ['open' => 'Unpaid', 'partial' => 'Part paid', 'paid' => 'Paid', 'void' => 'Cancelled'];
  $CARE  = ['planned' => 'Planned', 'done' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp

<div class="page-head">
  <div>
    <h1>Welcome back, {{ auth()->user()->name }}</h1>
    <div class="sub">Your next visit, what you owe and everything we have done for you, all in one place.</div>
  </div>
  <div class="sub">{{ now()->format('l, d F Y') }}</div>
</div>

@if($nextAppointment)
  <div class="hero-card">
    <div class="eyebrow">Your next visit</div>
    <div class="when">{{ $nextAppointment->scheduled_at->format('l, d F Y \a\t H:i') }}</div>
    <div class="meta">
      With {{ $nextAppointment->doctor?->name ?? 'one of our dentists' }}@if($nextAppointment->branch) at {{ $nextAppointment->branch->name }}@endif
    </div>
    @if($nextAppointment->branch?->address)
      <div class="meta">{{ $nextAppointment->branch->address }}</div>
    @endif
    <div class="meta">That is {{ $nextAppointment->scheduled_at->diffForHumans() }} — please come about ten minutes early.</div>
  </div>
@else
  <div class="hero-card quiet">
    <div class="eyebrow">No visit booked</div>
    <div class="when">Shall we find you a time?</div>
    <div class="meta">Tell us the dentist and the day that suit you, and reception will confirm a time with you.</div>
    <a href="{{ route('appointment-request.create') }}" class="btn">Request an appointment</a>
  </div>
@endif

<div class="kpi-grid">
  <div class="kpi accent">
    <div class="kpi-label">Upcoming visits</div>
    <div class="kpi-value">{{ $stats['upcoming'] }}</div>
    <div class="kpi-sub">{{ $stats['upcoming'] > 0 ? 'Booked and waiting for you' : 'Nothing booked yet' }}</div>
  </div>
  <div class="kpi{{ $outstanding > 0 ? ' money' : '' }}">
    <div class="kpi-label">Balance due</div>
    <div class="kpi-value">${{ number_format($outstanding, 2) }}</div>
    <div class="kpi-sub">{{ $outstanding > 0 ? 'Payable at reception' : 'All paid up — thank you' }}</div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Treatments completed</div>
    <div class="kpi-value">{{ $stats['treatments'] }}</div>
    <div class="kpi-sub">Work we have finished for you</div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Visits so far</div>
    <div class="kpi-value">{{ $stats['visits'] }}</div>
    <div class="kpi-sub">Times you have been in to see us</div>
  </div>
</div>

@if($nextVisitNote && $nextVisitNote->next_visit)
  <div class="alert-strip">
    <a class="alert-pill info" href="{{ route('appointment-request.create') }}">
      🗓 Your dentist suggested another look around {{ $nextVisitNote->next_visit->format('d/m/Y') }} — ask for a time
    </a>
  </div>
@endif

<div class="grid-2">
  <div>
    <div class="panel">
      <div class="panel-head">
        <h2>My appointment requests</h2>
        <a href="{{ route('appointment-request.create') }}" class="btn small">+ Request a visit</a>
      </div>
      @if($requests->isEmpty())
        <div class="empty">
          <span class="icon">📮</span>
          <p>You have not asked us for a visit yet.</p>
          <p><a href="{{ route('appointment-request.create') }}" class="btn small">Request an appointment</a></p>
        </div>
      @else
        <table>
          <thead><tr><th>Requested</th><th>Dentist</th><th>Clinic</th><th>Status</th><th>Clinic's reply</th></tr></thead>
          <tbody>
            @foreach($requests as $r)
              <tr>
                <td>
                  {{ $r->created_at->format('d/m/Y') }}
                  @if($r->preferred_date)<br><span class="muted">you asked for {{ $r->preferred_date->format('d/m/Y') }}</span>@endif
                </td>
                <td>{{ $r->doctor?->name ?? '—' }}</td>
                <td>{{ $r->branch?->name ?? '—' }}</td>
                <td><span class="badge reqstat-{{ $r->status }}">{{ $REQ[$r->status] ?? $r->status }}</span></td>
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
        <div class="panel-body"><a href="{{ route('my-requests') }}">See all my requests →</a></div>
      @endif
    </div>

    <div class="panel">
      <div class="panel-head"><h2>My visits</h2></div>
      @if($appointments->isEmpty())
        <div class="empty">
          <span class="icon">🦷</span>
          <p>No visits on record yet. Your first one will show up here.</p>
        </div>
      @else
        <table>
          <thead><tr><th>When</th><th>Dentist</th><th>Clinic</th><th>Status</th></tr></thead>
          <tbody>
            @foreach($appointments as $a)
              <tr>
                <td>{{ $a->scheduled_at->format('d/m/Y H:i') }}</td>
                <td>{{ $a->doctor?->name ?? '—' }}</td>
                <td>{{ $a->branch?->name ?? '—' }}</td>
                <td><span class="badge">{{ $VISIT[$a->status] ?? $a->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>

  <div>
    @if($careTeam->isNotEmpty())
      <div class="panel">
        <div class="panel-head"><h2>My care team</h2></div>
        @foreach($careTeam as $d)
          <div class="list-row">
            <div>
              <div class="who">{{ $d->name }}</div>
              <div class="when">{{ $d->branch?->name ?? 'Our clinic' }}</div>
            </div>
            <span class="badge job">Dentist</span>
          </div>
        @endforeach
      </div>
    @endif

    <div class="panel">
      <div class="panel-head"><h2>Quick actions</h2></div>
      <div class="qa-list">
        <a class="qa" href="{{ route('appointment-request.create') }}"><span class="ico">📅</span> Request an appointment</a>
        <a class="qa" href="{{ route('my-requests') }}">
          <span class="ico">📋</span> My requests
          @if($stats['pending_requests'] > 0)<span class="badge">{{ $stats['pending_requests'] }} waiting</span>@endif
        </a>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h2>My bills</h2></div>
  @if($invoices->isEmpty())
    <div class="empty">
      <span class="icon">🧾</span>
      <p>Nothing to pay. A bill appears here once we have done some work for you.</p>
    </div>
  @else
    @if($outstanding > 0)
      <div class="panel-body">
        <p>You still owe <strong>${{ number_format($outstanding, 2) }}</strong>.</p>
        <p class="muted">There is nothing to pay online — reception will take care of it on your next visit, in cash or by card.</p>
      </div>
    @endif
    <table>
      <thead><tr><th>Date</th><th>Total</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($invoices as $i)
          <tr>
            <td>{{ $i->created_at->format('d/m/Y') }}</td>
            <td>${{ number_format($i->total, 2) }}</td>
            <td><strong>${{ number_format($i->balance, 2) }}</strong></td>
            <td><span class="badge status-{{ $i->status }}">{{ $BILL[$i->status] ?? $i->status }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @if($lastPayment)
      <div class="panel-body">
        <p class="muted">Your last payment was ${{ number_format($lastPayment->amount, 2) }} by {{ $lastPayment->method }} on {{ $lastPayment->paid_at?->format('d/m/Y') }}.</p>
      </div>
    @endif
  @endif
</div>

<div class="panel">
  <div class="panel-head"><h2>My treatments</h2></div>
  @if($treatments->isEmpty())
    <div class="empty">
      <span class="icon">✨</span>
      <p>Nothing here yet. Any work your dentist plans or finishes will be listed here.</p>
    </div>
  @else
    <table>
      <thead><tr><th>Procedure</th><th>Cost</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        @foreach($treatments as $t)
          <tr>
            <td>{{ $t->procedure }}</td>
            <td>${{ number_format($t->cost, 2) }}</td>
            <td><span class="badge">{{ $CARE[$t->status] ?? $t->status }}</span></td>
            <td>{{ $t->created_at->format('d/m/Y') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
