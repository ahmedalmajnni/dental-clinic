@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<div class="toolbar">
  <h1>Invoices</h1>
</div>
@if($invoices->isEmpty())
  <div class="card"><p class="muted">No invoices yet. They are created automatically when a treatment is saved.</p></div>
@else
<table>
  <thead><tr><th>Date</th><th>Patient</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead>
  <tbody>
    @foreach($invoices as $i)
      <tr>
        <td>{{ $i->created_at->format('d/m/Y') }}</td>
        <td>{{ $i->patient->name }}</td>
        <td>${{ number_format($i->total, 2) }}</td>
        <td>${{ number_format($i->total - $i->balance, 2) }}</td>
        <td><strong>${{ number_format($i->balance, 2) }}</strong></td>
        <td><span class="badge status-{{ $i->status }}">{{ $i->status }}</span></td>
        <td><a href="{{ route('invoices.show', $i) }}" class="btn small secondary">View</a></td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
