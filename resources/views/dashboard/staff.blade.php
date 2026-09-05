@extends('layouts.app')
@section('title', 'Home')
@section('content')
@php
  $hour = now()->hour;
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
  $who = $employee?->name ?: auth()->user()->name;
  $role = $employee ? ucfirst(str_replace('_', ' ', $employee->job_title)) : 'Administrator';
  $specialty = $employee?->specialty;

  // A doctor sees only their own day, so naming the doctor on every row is noise.
  $showDoctor = ! ($isDoctor && ! $seesAll);
  $stillBooked = $todays->where('status', 'booked')->count();

    $hasAlerts = ($isAdmin && $pendingStaff > 0) || $pendingRequests > 0
      || $labAttention->isNotEmpty();

  $glance = [
    'specialty' => ['Specialties', 'specialties.index'],
    'employee' => ['Employees', 'employees.index'],
    'patient' => ['Patients', 'patients.index'],
    'appointment' => ['Appointments', 'appointments.index'],
    'treatment' => ['Treatments', 'treatments.index'],
    'report' => ['Clinical notes', null],
    'lab_case' => ['Lab cases', 'lab-cases.index'],
    'media' => ['Media', 'media.index'],
  ];
@endphp

<div class="dashboard-page">
<div class="page-head">
  <div>
    <h1>{{ $greeting }}, {{ $who }}</h1>
    <div class="sub">{{ $role }}@if($specialty) · {{ $specialty }}@endif</div>
  </div>
  <div class="sub">{{ now()->format('l, d F Y') }}</div>
</div>

@if($hasAlerts)
  <div class="alert-strip">
    @if($isAdmin && $pendingStaff > 0)
      <a class="alert-pill warn" href="{{ route('accounts.requests') }}">
        Staff accounts to approve <span class="count">{{ $pendingStaff }}</span>
      </a>
    @endif
    @if($pendingRequests > 0)
      <a class="alert-pill warn" href="{{ route('requests.index') }}">
        Appointment requests waiting <span class="count">{{ $pendingRequests }}</span>
      </a>
    @endif
    @if($labAttention->isNotEmpty())
      <a class="alert-pill info" href="{{ route('lab-cases.index') }}">
        Lab work due soon <span class="count">{{ $labAttention->count() }}</span>
      </a>
    @endif
  </div>
@endif

<div class="kpi-grid">
  <a class="kpi accent" href="{{ route('appointments.index') }}">
    <div class="kpi-label">Today's appointments</div>
    <div class="kpi-value">{{ $todays->count() }}</div>
    <div class="kpi-sub">{{ $todays->isEmpty() ? 'Nothing scheduled' : $stillBooked . ' still booked' }}</div>
  </a>
  <a class="kpi" href="{{ route('patients.index') }}">
    <div class="kpi-label">Patients</div>
    <div class="kpi-value">{{ $counts['patient'] }}</div>
    <div class="kpi-sub">On the books</div>
  </a>
</div>

<div class="grid-2">
  <div>
    <div class="panel">
      <div class="panel-head">
        <h2>Today's schedule</h2>
        <a href="{{ route('appointments.index') }}">All appointments →</a>
      </div>
      @if($todays->isEmpty())
        <div class="empty">
          <span class="icon">📅</span>
          Nothing booked for today. Enjoy the quiet.
        </div>
      @else
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Patient</th>
              @if($showDoctor)<th>Doctor</th>@endif
              <th>Specialty</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($todays as $a)
              <tr>
                <td>{{ $a->scheduled_at->format('H:i') }}</td>
                <td>{{ $a->patient?->name ?? '—' }}</td>
                @if($showDoctor)<td>{{ $a->doctor?->name ?? '—' }}</td>@endif
                <td>{{ $a->doctor?->specialty ?? '—' }}</td>
                <td><span class="badge">{{ $a->status }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    @if($upcoming->isNotEmpty())
      <div class="panel">
        <div class="panel-head">
          <h2>Coming up next 7 days</h2>
          <a href="{{ route('appointments.index') }}">All appointments →</a>
        </div>
        @foreach($upcoming as $a)
          <div class="list-row">
            <div>
              <div class="who">{{ $a->patient?->name ?? '—' }}</div>
              <div class="when">{{ $a->doctor?->name ?? '—' }}@if($a->doctor?->specialty) · {{ $a->doctor->specialty }}@endif</div>
            </div>
            <div class="when">{{ $a->scheduled_at->format('d/m/Y H:i') }}</div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <div>
    <div class="panel">
      <div class="panel-head"><h2>Quick actions</h2></div>
      <div class="qa-list">
        <a class="qa" href="{{ route('patients.create') }}"><span class="ico">🧑</span> New patient</a>
        <a class="qa" href="{{ route('appointments.create') }}"><span class="ico">📅</span> Book appointment</a>
        <a class="qa" href="{{ route('treatments.create') }}"><img class="qa-logo-icon" src="{{ asset('images/logo.svg') }}" alt=""> Record treatment</a>
        <a class="qa" href="{{ route('requests.index') }}"><span class="ico">📥</span> Appointment requests</a>
        <a class="qa" href="{{ route('lab-cases.create') }}"><span class="ico">🔬</span> New lab case</a>
        <a class="qa" href="{{ route('media.create') }}"><span class="ico">🖼</span> Upload media</a>
        @if($isAdmin)
          <a class="qa" href="{{ route('specialties.index') }}"><span class="ico">🦷</span> Specialties</a>
          <a class="qa" href="{{ route('employees.index') }}"><span class="ico">👥</span> Employees</a>
          <a class="qa" href="{{ route('accounts.index') }}"><span class="ico">🔑</span> Accounts</a>
        @endif
      </div>
    </div>

    @if($labAttention->isNotEmpty())
      <div class="panel">
        <div class="panel-head">
          <h2>Lab work needing attention</h2>
          <a href="{{ route('lab-cases.index') }}">All lab cases →</a>
        </div>
        @foreach($labAttention as $c)
          <div class="list-row">
            <div>
              <div class="who">{{ $c->type }}</div>
              <div class="when">{{ $c->patient?->name ?? '—' }}</div>
            </div>
            <span class="badge {{ $c->due_date->lt(today()) ? 'overdue' : 'due-soon' }}">
              {{ $c->due_date->format('d/m/Y') }}
            </span>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head">
      <h2>New patients</h2>
      <a href="{{ route('patients.index') }}">All patients →</a>
    </div>
    @if($recentPatients->isEmpty())
      <div class="empty">
        <span class="icon">🧑</span>
        No patients registered yet.
      </div>
    @else
      @foreach($recentPatients as $p)
        <div class="list-row">
          <div>
            <div class="who">{{ $p->name }}</div>
            <div class="when">{{ $p->phone ?: 'No phone' }}</div>
          </div>
          <div class="when">{{ $p->created_at->format('d/m/Y') }}</div>
        </div>
      @endforeach
    @endif
  </div>
</div>

@if($isAdmin)
  <div class="panel">
    <div class="panel-head"><h2>Clinic at a glance</h2></div>
    <div class="panel-body">
      <div class="stat-grid">
        @foreach($counts as $key => $n)
          @php($label = $glance[$key] ?? [$key, null])
          <div class="stat">
            <div class="n">{{ $n }}</div>
            <div class="l">
              @if($label[1])
                <a href="{{ route($label[1]) }}">{{ $label[0] }}</a>
              @else
                {{ $label[0] }}
              @endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
@endif
 </div>
@endsection
