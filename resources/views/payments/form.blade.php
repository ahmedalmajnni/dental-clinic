@extends('layouts.app')
@php($isEdit = $payment !== null)
@section('title', $isEdit ? 'Edit payment' : 'Record a payment')
@section('content')
<div class="card" style="max-width:520px;">
  <h1>{{ $isEdit ? 'Edit payment' : 'Record a payment' }}</h1>
  <p class="muted">The payment is applied to the patient's unpaid invoices automatically, oldest first. Any extra becomes account credit.</p>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($isEdit)
      @method('PUT')
      <label>Patient</label>
      <p>{{ $payment->patient->name }}</p>
    @else
      <label for="patient_id">Patient</label>
      <select id="patient_id" name="patient_id" required>
        <option value="">— choose patient —</option>
        @foreach($patients as $p)
          <option value="{{ $p->id }}" @selected(old('patient_id') === $p->id)>
            {{ $p->name }} — owes ${{ number_format($p->owed, 2) }}
          </option>
        @endforeach
      </select>
    @endif

    <label for="amount">Amount</label>
    <input type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount', $payment?->amount) }}" required autofocus>

    <label for="method">Method</label>
    <select id="method" name="method">
      @foreach($methods as $m)
        <option value="{{ $m }}" @selected(old('method', $payment?->method ?: 'cash') === $m)>{{ $m }}</option>
      @endforeach
    </select>

    <label for="paid_at">Date received (optional)</label>
    <input type="date" id="paid_at" name="paid_at" value="{{ old('paid_at', $payment?->paid_at?->format('Y-m-d')) }}">

    <div class="actions" style="margin-top:16px;">
      <button type="submit" class="btn">{{ $isEdit ? 'Save changes' : 'Record payment' }}</button>
      <a href="{{ route('payments.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
