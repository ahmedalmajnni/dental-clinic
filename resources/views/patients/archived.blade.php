@extends('layouts.app')
@section('title', 'Archived patients')
@section('content')
<div class="toolbar">
  <h1>Archived patients</h1>
  <a href="{{ route('patients.index') }}" class="btn secondary">← Back to patients</a>
</div>
<p class="muted">Archived patients are hidden from the main list but keep all their history (invoices, appointments, treatments). <strong>Restore</strong> brings them back. <strong>Delete permanently</strong> erases the patient <em>and all of their records</em> (invoices, payments, appointments, treatments, notes, lab cases, media) — this cannot be undone.</p>

@if($patients->isEmpty())
  <div class="card"><p class="muted">No archived patients.</p></div>
@else
<table>
  <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th></th></tr></thead>
  <tbody>
    @foreach($patients as $p)
      <tr>
        <td>{{ $p->name }}</td>
        <td>{{ $p->phone ?: '—' }}</td>
        <td>{{ $p->email ?: '—' }}</td>
        <td class="actions">
          <form method="POST" action="{{ route('patients.restore', $p->id) }}">
            @csrf
            <button class="btn small">Restore</button>
          </form>
          <form method="POST" action="{{ route('patients.force-delete', $p->id) }}"
                onsubmit="return confirm('PERMANENTLY DELETE {{ $p->name }} and ALL of their records (invoices, payments, appointments, treatments, notes, lab cases, media)?\n\nThis erases their financial history and CANNOT be undone.');">
            @csrf @method('DELETE')
            <button class="btn small danger">Delete permanently</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
