@extends('layouts.app')
@section('title', 'Payments — ' . $patient->name)
@section('content')
<div class="toolbar">
  <h1>Payments — {{ $patient->name }}</h1>
  <a href="{{ route('payments.index') }}" class="btn secondary">← All patients</a>
</div>
@if($payments->isEmpty())
  <div class="card"><p class="muted">No payments recorded for this patient.</p></div>
@else
<table>
  <thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Applied to invoices</th><th>Unused credit</th></tr></thead>
  <tbody>
    @foreach($payments as $p)
      <tr>
        <td>{{ $p->paid_at->format('d/m/Y') }}</td>
        <td><span class="badge">{{ $p->method }}</span></td>
        <td>${{ number_format($p->amount, 2) }}</td>
        <td>${{ number_format($p->allocated, 2) }}</td>
        <td>{{ $p->unallocated > 0 ? '$' . number_format($p->unallocated, 2) : '—' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
