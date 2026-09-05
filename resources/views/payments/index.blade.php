@extends('layouts.app')
@section('title', 'Payments')
@section('content')
<div class="toolbar">
  <h1>Payments by patient</h1>
</div>
@if($patients->isEmpty())
  <div class="card"><p class="muted">No payments recorded yet.</p></div>
@else
<table>
  <thead><tr><th>Patient</th><th>Payments</th><th>Total paid</th><th>Unused credit</th><th></th></tr></thead>
  <tbody>
    @foreach($patients as $p)
      <tr>
        <td>{{ $p->name }}</td>
        <td>{{ $p->payments_count }}</td>
        <td>${{ number_format($p->total_paid, 2) }}</td>
        <td>{{ $p->credit > 0 ? '$' . number_format($p->credit, 2) : '—' }}</td>
        <td><a href="{{ route('payments.patient', $p) }}" class="btn small secondary">View payments</a></td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
