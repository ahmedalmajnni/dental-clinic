@extends('layouts.app')
@section('title', 'Availability — ' . $doctor->name)
@section('content')
@php
  $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  $slotChoices = [15, 20, 30, 45, 60];
  $byDay = $doctor->availability->sortBy('start_time')->groupBy('weekday');
  $exceptions = $doctor->timeOff->filter(fn ($t) => $t->on_date && $t->on_date->gte(today()))->sortBy('on_date');
  $isAdmin = auth()->user()->role === 'admin';
@endphp

<div class="toolbar">
  <h1>Availability — {{ $doctor->name }}</h1>
  @if($isAdmin)
    <a href="{{ route('availability.index') }}" class="btn secondary">← All doctors</a>
  @else
    <a href="{{ route('dashboard') }}" class="btn secondary">← Dashboard</a>
  @endif
</div>

@if($byDay->isEmpty())
  <p class="muted"><span class="badge kind-off">Not bookable</span> No weekly hours yet — add at least one range so patients can be scheduled.</p>
@endif

<div class="panel">
  <div class="panel-head"><h2>Weekly hours</h2></div>
  <div class="panel-body">
    <form method="POST" action="{{ route('availability.update', $doctor) }}">
      @csrf @method('PUT')
      @foreach($dayNames as $wd => $dayName)
        @php($rows = $byDay[$wd] ?? collect())
        <div class="avail-day {{ $rows->isEmpty() ? 'is-empty' : '' }}" id="avail-day-{{ $wd }}">
          <div class="avail-day-head">
            <span class="day-name">{{ $dayName }}</span>
            <button type="button" class="btn small secondary" onclick="addRange({{ $wd }})">+ Add range</button>
          </div>
          <div id="day-rows-{{ $wd }}">
            @foreach($rows as $r)
              <div class="time-row">
                <input type="hidden" name="weekday[]" value="{{ $wd }}">
                <input type="time" name="start_time[]" value="{{ substr($r->start_time, 0, 5) }}">
                <span class="sep">to</span>
                <input type="time" name="end_time[]" value="{{ substr($r->end_time, 0, 5) }}">
                <select name="slot_minutes[]">
                  @foreach($slotChoices as $m)
                    <option value="{{ $m }}" {{ (int) $r->slot_minutes === $m ? 'selected' : '' }}>{{ $m }}</option>
                  @endforeach
                </select>
                <span class="sep">min slots</span>
                <button type="button" class="row-remove" onclick="removeRange(this)" title="Remove this range">✕</button>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
      <div class="actions" style="margin-top:12px;">
        <button type="submit" class="btn">Save weekly hours</button>
      </div>
      <p class="muted">Saving replaces all weekly hours for this doctor. A weekday left with no ranges is a day off.</p>
    </form>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h2>Days off &amp; extra hours</h2></div>
  <div class="panel-body">
    @if($exceptions->isEmpty())
      <p class="muted">No upcoming exceptions.</p>
    @else
      <table class="exceptions">
        <thead><tr><th>Date</th><th>Kind</th><th>Time</th><th>Reason</th><th></th></tr></thead>
        <tbody>
          @foreach($exceptions as $t)
            <tr>
              <td class="narrow">{{ $t->on_date->format('d/m/Y') }}</td>
              <td class="narrow"><span class="badge kind-{{ $t->kind }}">{{ $t->kind === 'off' ? 'Day off' : 'Extra hours' }}</span></td>
              <td class="narrow">
                @if($t->start_time && $t->end_time)
                  {{ substr($t->start_time, 0, 5) }}–{{ substr($t->end_time, 0, 5) }}@if($t->kind === 'extra') · {{ $t->slot_minutes ?: 30 }} min slots @endif
                @else
                  Whole day
                @endif
              </td>
              <td>{{ $t->reason ?: '—' }}</td>
              <td class="narrow actions">
                <form method="POST" action="{{ route('availability.time-off.destroy', [$doctor, $t]) }}" onsubmit="return confirm('Remove this exception?');">
                  @csrf @method('DELETE')
                  <button class="btn small danger">Remove</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif

    <form method="POST" action="{{ route('availability.time-off.store', $doctor) }}" style="margin-top:12px;">
      @csrf
      <div class="time-row">
        <input type="date" name="on_date" min="{{ now()->toDateString() }}" value="{{ old('on_date') }}" required>
        <select name="kind">
          <option value="off" {{ old('kind') === 'off' ? 'selected' : '' }}>Day off</option>
          <option value="extra" {{ old('kind') === 'extra' ? 'selected' : '' }}>Extra hours</option>
        </select>
        <input type="time" name="start_time" value="{{ old('start_time') }}" title="Start time">
        <span class="sep">to</span>
        <input type="time" name="end_time" value="{{ old('end_time') }}" title="End time">
        <select name="slot_minutes" title="Slot length for extra hours">
          @foreach($slotChoices as $m)
            <option value="{{ $m }}" {{ (int) old('slot_minutes', 30) === $m ? 'selected' : '' }}>{{ $m }}</option>
          @endforeach
        </select>
        <span class="sep">min slots</span>
        <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Reason (optional)" maxlength="255">
        <button type="submit" class="btn small">Add</button>
      </div>
      <p class="muted">Leave both times blank on a day off to block the whole day; fill them in to block just that range. Extra hours need both times.</p>
    </form>
  </div>
</div>

<template id="range-template">
  <div class="time-row">
    <input type="hidden" name="weekday[]" value="__WD__">
    <input type="time" name="start_time[]" value="09:00">
    <span class="sep">to</span>
    <input type="time" name="end_time[]" value="13:00">
    <select name="slot_minutes[]">
      @foreach($slotChoices as $m)
        <option value="{{ $m }}" {{ $m === 30 ? 'selected' : '' }}>{{ $m }}</option>
      @endforeach
    </select>
    <span class="sep">min slots</span>
    <button type="button" class="row-remove" onclick="removeRange(this)" title="Remove this range">✕</button>
  </div>
</template>

<script>
  function addRange(weekday) {
    var box = document.getElementById('day-rows-' + weekday);
    var html = document.getElementById('range-template').innerHTML.split('__WD__').join(weekday);
    box.insertAdjacentHTML('beforeend', html);
    document.getElementById('avail-day-' + weekday).classList.remove('is-empty');
  }

  function removeRange(btn) {
    // The weekday is a hidden input inside the same row, so dropping the row
    // keeps the four posted arrays index-aligned.
    var row = btn.closest('.time-row');
    var box = row.parentNode;
    row.remove();
    if (!box.querySelector('.time-row')) box.parentNode.classList.add('is-empty');
  }
</script>
@endsection
