@extends('layouts.app')
@php($isEdit = $appointment->exists)
@section('title', $isEdit ? 'Edit appointment' : 'New appointment')
@section('content')
<div class="card" style="max-width:560px;">
  <h1>{{ $isEdit ? 'Edit appointment' : 'New appointment' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="patient_id">Patient</label>
    <select id="patient_id" name="patient_id" required>
      <option value="">— choose patient —</option>
      @foreach($patients as $p)
        <option value="{{ $p->id }}" @selected(old('patient_id', $appointment->patient_id) === $p->id)>{{ $p->name }}</option>
      @endforeach
    </select>

    <label for="doctor_id">Doctor</label>
    <select id="doctor_id" name="doctor_id" required>
      <option value="">— choose doctor —</option>
      @foreach($doctors as $d)
        <option value="{{ $d->id }}" @selected(old('doctor_id', $appointment->doctor_id) === $d->id)>{{ $d->name }}{{ $d->specialty ? ' - '.$d->specialty : '' }}</option>
      @endforeach
    </select>

    <label for="branch_id">Branch</label>
    <select id="branch_id" name="branch_id" required>
      <option value="">— choose branch —</option>
      @foreach($branches as $b)
        <option value="{{ $b->id }}" @selected(old('branch_id', $appointment->branch_id) === $b->id)>{{ $b->name }}</option>
      @endforeach
    </select>

    <label for="scheduled_at">Date &amp; time</label>
    <input type="datetime-local" id="scheduled_at" name="scheduled_at" required
           value="{{ old('scheduled_at', optional($appointment->scheduled_at)->format('Y-m-d\TH:i')) }}">

    <label for="slot_pick">Open times</label>
    <select id="slot_pick">
      <option value="">— choose a doctor and a date —</option>
    </select>
    <p class="muted" id="slot_note">Pick a doctor and a date and this lists the times they are actually free.</p>

    @if(auth()->user()->role === 'admin')
      {{-- Reception must not be able to quietly fill a doctor's day off; the
           clinic owner sometimes has to squeeze in an emergency. --}}
      <label for="ignore_availability" style="font-weight:400;">
        <input type="checkbox" id="ignore_availability" name="ignore_availability" value="1"
               style="width:auto; margin-right:6px;" @checked(old('ignore_availability'))>
        Book outside the doctor's hours
      </label>
    @endif

    <label for="status">Status</label>
    <select id="status" name="status">
      @foreach($statuses as $s)
        <option value="{{ $s }}" @selected(old('status', $appointment->status) === $s)>{{ $s }}</option>
      @endforeach
    </select>

    <hr style="margin:22px 0; border:none; border-top:1px solid var(--border);">
    <h2 style="font-size:1.05rem; margin:0 0 4px;">Clinical notes</h2>
    <p class="muted" style="margin-top:0;">Fill these in after the visit — leave blank when booking.</p>

    <label for="diagnosis">Diagnosis</label>
    <input type="text" id="diagnosis" name="diagnosis" value="{{ old('diagnosis', $report->diagnosis ?? '') }}">

    <label for="notes">Notes</label>
    <textarea id="notes" name="notes" placeholder="Treatment given, advice, follow-up…">{{ old('notes', $report->notes ?? '') }}</textarea>

    <label for="next_visit">Next visit</label>
    <input type="date" id="next_visit" name="next_visit"
           value="{{ old('next_visit', optional($report->next_visit ?? null)->format('Y-m-d')) }}">

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('appointments.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>

<script>
  var SLOTS_URL = @json(route('availability.slots'));
  var IGNORE_ID = @json($isEdit ? $appointment->id : null);

  var doctorField = document.getElementById('doctor_id');
  var whenField = document.getElementById('scheduled_at');
  var slotField = document.getElementById('slot_pick');
  var slotNote = document.getElementById('slot_note');
  var pending = 0;

  function setSlots(times, message) {
    slotField.innerHTML = '';
    var first = document.createElement('option');
    first.value = '';
    first.textContent = times.length ? '— pick a free time —' : '— no free times —';
    slotField.appendChild(first);

    var chosen = whenField.value.slice(11, 16);
    times.forEach(function (t) {
      var opt = document.createElement('option');
      opt.value = t;
      opt.textContent = t;
      if (t === chosen) opt.selected = true;
      slotField.appendChild(opt);
    });
    slotNote.textContent = message;
  }

  function loadSlots() {
    var doctor = doctorField.value;
    var date = whenField.value.slice(0, 10);
    if (!doctor || !date) {
      setSlots([], 'Pick a doctor and a date and this lists the times they are actually free.');
      return;
    }

    // Answers can come back out of order when the user keeps changing the date,
    // so only the newest request is allowed to paint the list.
    var ticket = ++pending;
    var url = SLOTS_URL + '?doctor_id=' + encodeURIComponent(doctor) + '&date=' + encodeURIComponent(date);
    if (IGNORE_ID) url += '&ignore=' + encodeURIComponent(IGNORE_ID);

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (ticket !== pending) return;
        var times = data.slots || [];
        setSlots(times, times.length
          ? 'Choosing a time here fills in the box above.'
          : 'No open times that day — try another date, or set the doctor\'s hours under Availability.');
      })
      .catch(function () {
        if (ticket === pending) setSlots([], 'Could not load open times.');
      });
  }

  slotField.addEventListener('change', function () {
    if (!slotField.value) return;
    whenField.value = whenField.value.slice(0, 10) + 'T' + slotField.value;
  });

  doctorField.addEventListener('change', loadSlots);
  whenField.addEventListener('change', loadSlots);
  loadSlots();
</script>
@endsection
