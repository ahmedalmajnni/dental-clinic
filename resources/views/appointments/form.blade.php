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

    <input type="hidden" id="scheduled_at" name="scheduled_at"
           value="{{ old('scheduled_at', optional($appointment->scheduled_at)->format('Y-m-d\TH:i')) }}">

    <label for="slot_pick">Available times</label>
    <select id="slot_pick" required>
      <option value="">— choose a doctor —</option>
    </select>
    <p class="muted" id="slot_note">Pick a doctor to see their available times.</p>

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
    <select id="next_visit" name="next_visit">
      <option value="">— choose an available time —</option>
    </select>
    <p class="muted" id="next_visit_note">Choose a doctor to see available times for the next visit.</p>

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
  var nextVisitField = document.getElementById('next_visit');
  var nextVisitNote = document.getElementById('next_visit_note');
  var savedNextVisit = @json(old('next_visit', optional($report->next_visit ?? null)->format('Y-m-d')));
  var pending = 0;

  function displayTime(time) {
    var parts = time.split(':');
    var hour = Number(parts[0]);
    var suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12;
    return hour + ':' + parts[1] + ' ' + suffix;
  }

  function setSlots(times, message) {
    slotField.innerHTML = '';
    var first = document.createElement('option');
    first.value = '';
    first.textContent = times.length ? '— pick an open time —' : '— no free times —';
    slotField.appendChild(first);

    var chosen = whenField.value.slice(0, 16);
    times.forEach(function (t) {
      var date = typeof t === 'string' ? '' : t.date;
      var time = typeof t === 'string' ? t : t.time;
      var opt = document.createElement('option');
      opt.value = date + '|' + time;
      opt.textContent = date + ' at ' + displayTime(time);
      if (date + 'T' + time === chosen) opt.selected = true;
      slotField.appendChild(opt);
    });
    slotNote.textContent = message;

    nextVisitField.innerHTML = '';
    var nextVisitFirst = document.createElement('option');
    nextVisitFirst.value = '';
    nextVisitFirst.textContent = times.length ? '— choose an available time —' : '— no available times —';
    nextVisitField.appendChild(nextVisitFirst);
    times.forEach(function (t) {
      var date = typeof t === 'string' ? '' : t.date;
      var time = typeof t === 'string' ? t : t.time;
      var nextVisitOption = document.createElement('option');
      nextVisitOption.value = date + '|' + time;
      nextVisitOption.textContent = date + ' at ' + displayTime(time);
      if (date === savedNextVisit) nextVisitOption.selected = true;
      nextVisitField.appendChild(nextVisitOption);
    });
    nextVisitNote.textContent = times.length
      ? 'Choose an available time for the patient\'s next visit.'
      : 'No available times in the next 30 days.';
  }

  function loadSlots() {
    var doctor = doctorField.value;
    if (!doctor) {
      setSlots([], 'Pick a doctor to see their available times.');
      return;
    }

    // Answers can come back out of order when the user keeps changing the date,
    // so only the newest request is allowed to paint the list.
    var ticket = ++pending;
    var url = SLOTS_URL + '?doctor_id=' + encodeURIComponent(doctor);
    if (IGNORE_ID) url += '&ignore=' + encodeURIComponent(IGNORE_ID);

    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (ticket !== pending) return;
        var times = data.slots || [];
        setSlots(times, times.length
          ? 'Choose an available time to book the appointment.'
          : 'No available times in the next 30 days.');
      })
      .catch(function () {
        if (ticket === pending) setSlots([], 'Could not load open times.');
      });
  }

  slotField.addEventListener('change', function () {
    if (!slotField.value) return;
    var picked = slotField.value.split('|');
    whenField.value = picked[0] + 'T' + picked[1];
  });

  doctorField.addEventListener('change', loadSlots);
  loadSlots();
</script>
@endsection
