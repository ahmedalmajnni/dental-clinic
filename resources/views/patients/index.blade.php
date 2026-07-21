@extends('layouts.app')
@section('title', 'Patients')
@section('content')
<div class="toolbar">
  <h1>Patients</h1>
  <div class="actions">
    @if($archivedCount > 0)
      <a href="{{ route('patients.archived') }}" class="btn secondary">🗄 Archived ({{ $archivedCount }})</a>
    @endif
    <a href="{{ route('patients.create') }}" class="btn">+ New patient</a>
  </div>
</div>
@if($patients->isEmpty())
  <div class="card"><p class="muted">No patients yet.</p></div>
@else
<table>
  <thead><tr><th>Name</th><th>Date of birth</th><th>Phone</th><th>Email</th><th></th></tr></thead>
  <tbody>
    @foreach($patients as $p)
      <tr>
        <td>{{ $p->name }}</td>
        <td>{{ $p->dob ? $p->dob->format('d/m/Y') : '—' }}</td>
        <td>{{ $p->phone ?: '—' }}</td>
        <td>{{ $p->email ?: '—' }}</td>
        <td class="actions">
          <a href="{{ route('patients.edit', $p) }}" class="btn small secondary">Edit</a>
          <form method="POST" action="{{ route('patients.destroy', $p) }}" onsubmit="return confirm('Archive this patient? Their history stays intact and you can restore them anytime.');">
            @csrf @method('DELETE')
            <button class="btn small secondary">Archive</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
