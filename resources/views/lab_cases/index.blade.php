@extends('layouts.app')
@section('title', 'Lab cases')
@section('content')
<div class="toolbar">
  <h1>Lab cases</h1>
  <a href="{{ route('lab-cases.create') }}" class="btn">+ New lab case</a>
</div>
@if($labCases->isEmpty())
  <div class="card"><p class="muted">No lab cases yet.</p></div>
@else
<table>
  <thead><tr><th>Patient</th><th>Type</th><th>Doctor</th><th>Due</th><th>Status</th><th>Cost</th><th></th></tr></thead>
  <tbody>
    @foreach($labCases as $lc)
      <tr>
        <td>{{ $lc->patient->name }}</td>
        <td>{{ $lc->type }}</td>
        <td>{{ $lc->doctor->name }}</td>
        <td>{{ $lc->due_date ? $lc->due_date->format('d/m/Y') : '—' }}</td>
        <td><span class="badge">{{ str_replace('_', ' ', $lc->status) }}</span></td>
        <td>${{ number_format($lc->cost, 2) }}</td>
        <td class="actions">
          <a href="{{ route('lab-cases.edit', $lc) }}" class="btn small secondary edit-action">Edit</a>
          <form method="POST" action="{{ route('lab-cases.destroy', $lc) }}" onsubmit="return confirm('Delete this lab case?');">
            @csrf @method('DELETE')
            <button class="btn small danger">Delete</button>
          </form>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif
@endsection
