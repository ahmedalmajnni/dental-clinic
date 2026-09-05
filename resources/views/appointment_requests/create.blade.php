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
      <option value="" data-specialty="">— choose doctor —</option>
      @foreach($doctors as $d)
        <option value="{{ $d->id }}" data-specialty="{{ $d->specialty ?? '' }}" {{ old('doctor_id') === $d->id ? 'selected' : '' }}>
          {{ $d->name }}
        </option>
      @endforeach
    </select>

    <label for="specialty">Specialty / clinic</label>
    <input type="text" id="specialty" value="" readonly
           placeholder="Selected automatically from your doctor"
           style="background:#f8fafc; color:var(--muted);">
    <p class="muted">Each doctor works in one specialty, so this is set for you — you can't book a doctor under a different specialty.</p>

    <label for="preferred_slot">Available time</label>
    <select id="preferred_slot" name="preferred_slot" required>
      <option value="">— choose a doctor first —</option>
    </select>
    <p class="muted" id="slot_note">Choose a doctor to see their available times.</p>

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
    document.getElementById('specialty').value = opt ? (opt.getAttribute('data-specialty') || '') : '';
  }
  showSpecialty();

  var doctorField = document.getElementById('doctor_id');
  var slotField = document.getElementById('preferred_slot');
  var slotNote = document.getElementById('slot_note');
  var slotsUrl = @json(route('appointment-request.slots'));

  function displayTime(time) {
    var parts = time.split(':');
    var hour = Number(parts[0]);
    var suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12 || 12;
    return hour + ':' + parts[1] + ' ' + suffix;
  }

  function loadSlots() {
    slotField.innerHTML = '<option value="">— loading available times —</option>';
    if (!doctorField.value) {
      slotField.innerHTML = '<option value="">— choose a doctor first —</option>';
      slotNote.textContent = 'Choose a doctor to see their available times.';
      return;
    }
    fetch(slotsUrl + '?doctor_id=' + encodeURIComponent(doctorField.value), { headers: { 'Accept': 'application/json' } })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        slotField.innerHTML = '<option value="">— choose an available time —</option>';
        (data.slots || []).forEach(function (slot) {
          var option = document.createElement('option');
          option.value = slot.date + '|' + slot.time;
          option.textContent = slot.date + ' at ' + displayTime(slot.time);
          slotField.appendChild(option);
        });
        slotNote.textContent = data.slots && data.slots.length ? 'Select a time that works for you.' : 'No available times for this doctor.';
      })
      .catch(function () {
        slotField.innerHTML = '<option value="">— could not load times —</option>';
        slotNote.textContent = 'Could not load available times.';
      });
  }

  doctorField.addEventListener('change', function () {
    showSpecialty();
    loadSlots();
  });
  loadSlots();
</script>
@endsection
