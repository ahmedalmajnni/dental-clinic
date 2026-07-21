@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
  $LABELS = ['branch'=>'Branches','employee'=>'Employees','patient'=>'Patients','appointment'=>'Appointments',
             'treatment'=>'Treatments','report'=>'Clinical notes','lab_case'=>'Lab cases','media'=>'Media'];
@endphp
<h1>Dashboard</h1>

<div class="stat-grid" style="margin-bottom:24px;">
  @foreach($counts as $k => $n)
    <div class="stat">
      <div class="n">{{ $n }}</div>
      <div class="l">{{ $LABELS[$k] ?? $k }}</div>
    </div>
  @endforeach
  <div class="stat">
    <div class="n" style="color:#dc2626;">${{ number_format($outstanding, 2) }}</div>
    <div class="l"><a href="{{ route('invoices.index') }}">Outstanding</a></div>
  </div>
  <div class="stat">
    <div class="n" style="color:{{ $pendingRequests > 0 ? '#854d0e' : 'inherit' }};">{{ $pendingRequests }}</div>
    <div class="l"><a href="{{ route('requests.index') }}">Pending requests</a></div>
  </div>
  @if(auth()->user()->role === 'admin')
    <div class="stat">
      <div class="n" style="color:{{ $pendingStaff > 0 ? '#854d0e' : 'inherit' }};">{{ $pendingStaff }}</div>
      <div class="l"><a href="{{ route('accounts.requests') }}">Staff requests</a></div>
    </div>
  @endif
</div>

<div class="card">
  <h2 style="margin-top:0;font-size:1.15rem;">Today's appointments</h2>
  @if($todays->isEmpty())
    <p class="muted">No appointments scheduled for today.</p>
  @else
    <table>
      <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($todays as $a)
          <tr>
            <td>{{ $a->scheduled_at->format('H:i') }}</td>
            <td>{{ $a->patient->name }}</td>
            <td>{{ $a->doctor->name }}</td>
            <td><span class="badge">{{ $a->status }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
