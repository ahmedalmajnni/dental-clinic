@extends('layouts.app')
@section('title', 'Invoice')
@section('content')
<div class="toolbar">
  <h1>Invoice for {{ $invoice->patient->name }}</h1>
  <a href="{{ route('invoices.index') }}" class="btn secondary">← All invoices</a>
</div>

<div class="card">
  <div style="display:flex; gap:32px; flex-wrap:wrap; align-items:baseline;">
    <div><div class="muted">Total charged</div><div style="font-size:1.4rem;">${{ number_format($invoice->total, 2) }}</div></div>
    <div><div class="muted">Paid</div><div style="font-size:1.4rem;">${{ number_format($invoice->total - $invoice->balance, 2) }}</div></div>
    <div><div class="muted">Balance due</div><div style="font-size:1.4rem; color:#dc2626;"><strong>${{ number_format($invoice->balance, 2) }}</strong></div></div>
    <div><div class="muted">Status</div><span class="badge status-{{ $invoice->status }}">{{ $invoice->status }}</span></div>
  </div>
</div>

<div class="card">
  <h2 style="margin-top:0; font-size:1.1rem;">Charges</h2>
  @if($lines->isEmpty())
    <p class="muted">No charges on this invoice.</p>
  @else
    <table>
      <thead><tr><th>Date</th><th>Description</th><th>Treatment status</th><th style="text-align:right;">Amount</th></tr></thead>
      <tbody>
        @foreach($lines as $l)
          <tr>
            <td>{{ optional($l->treatment?->created_at ?? $l->labCase?->created_at ?? $l->media?->taken_at)->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $l->description ?: '—' }}</td>
            <td><span class="badge">{{ optional($l->treatment)->status ?? '—' }}</span></td>
            <td style="text-align:right;">${{ number_format($l->amount, 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card">
  <h2 style="margin-top:0; font-size:1.1rem;">Payments applied</h2>
  @if($payments->isEmpty())
    <p class="muted">No payments recorded against this invoice yet.</p>
  @else
    <table>
      <thead><tr><th>Date</th><th>Method</th><th style="text-align:right;">Amount</th></tr></thead>
      <tbody>
        @foreach($payments as $pm)
          <tr>
            <td>{{ optional($pm->payment->paid_at)->format('d/m/Y') }}</td>
            <td><span class="badge">{{ $pm->payment->method }}</span></td>
            <td style="text-align:right;">${{ number_format($pm->amount, 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

@if($invoice->status !== 'paid' && $invoice->balance > 0)
<div class="card" style="max-width:520px;">
  <h2 style="margin-top:0; font-size:1.1rem;">Make a partial payment</h2>
  <form method="POST" action="{{ route('invoices.pay', $invoice) }}">
    @csrf
    <label for="amount">Amount (balance due: ${{ number_format($invoice->balance, 2) }})</label>
    <input type="number" id="amount" name="amount" step="0.01" min="0.01"
           max="{{ number_format($invoice->balance, 2, '.', '') }}" required>

    <label for="method">Method</label>
    <select id="method" name="method">
      @foreach($methods as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
    </select>

    <label for="paid_at">Date received (optional)</label>
    <input type="date" id="paid_at" name="paid_at">

    <div class="actions" style="margin-top:16px;">
      <button type="submit" class="btn">Save partial payment</button>
    </div>
  </form>
</div>
@endif

@endsection
