@extends('layouts.app')
@section('title', 'Request an appointment')
@section('content')
<div class="card" style="max-width:560px;">
  <h1>Request an appointment</h1>
  <p class="muted">Choose your doctor — their specialty/clinic is selected automatically. The clinic will confirm a time, and you'll see it under "My requests".</p>
  @if($doctors->isEmpty())
    <p class="muted">Sorry, no doctors are available to book right now. Please try again later.</p>
  @else
  <form method="POST" action="{{ route('appointment-request.store') }}">
    @csrf
    <label for="doctor_id">Doctor</label>
    <select id="doctor_id" name="doctor_id" required onchange="showSpecialty()">
      <option value="" data-branch="">— choose doctor —</option>
      @foreach($doctors as $d)
        <option value="{{ $d->id }}" data-branch="{{ $d->branch->name ?? '' }}" {{ old('doctor_id') === $d->id ? 'selected' : '' }}>
          {{ $d->name }}@if($d->branch) — {{ $d->branch->name }}@endif
        </option>
      @endforeach
    </select>

    <label for="specialty">Specialty / clinic</label>
    <input type="text" id="specialty" value="" readonly
           placeholder="Selected automatically from your doctor"
           style="background:#f8fafc; color:var(--muted);">
    <p class="muted">Each doctor works in one specialty, so this is set for you — you can't book a doctor under a different specialty.</p>

    <label for="preferred_date">Preferred date (optional)</label>
    <input type="date" id="preferred_date" name="preferred_date" value="{{ old('preferred_date') }}">

    <label for="note">Note to the clinic (optional)</label>
    <textarea id="note" name="note" placeholder="e.g. mornings are better for me">{{ old('note') }}</textarea>

    <div class="actions" style="margin-top:16px;">
      <button type="submit" class="btn">Submit request</button>
      <a href="{{ route('dashboard') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
  @endif
</div>

<script>
  function showSpecialty() {
    var sel = document.getElementById('doctor_id');
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('specialty').value = opt ? (opt.getAttribute('data-branch') || '') : '';
  }
  showSpecialty();
</script>
@endsection
