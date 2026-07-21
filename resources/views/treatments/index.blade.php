@extends('layouts.app')
@section('title', 'Treatments')
@section('content')
<div class="toolbar">
  <h1>Treatments</h1>
  <a href="{{ route('treatments.create') }}" class="btn">+ New treatment</a>
</div>
@if($treatments->isEmpty())
  <div class="card"><p class="muted">No treatments yet.</p></div>
@else
<table>
  <thead><tr><th>Patient</th><th>Procedure</th><th>Cost</th><th>Status</th><th>Date</th><th></th></tr></thead>
  <tbody>
    @foreach($treatments as $t)
      <tr>
        <td>{{ $t->patient->name }}</td>
        <td>{{ $t->procedure }}</td>
        <td>${{ number_format($t->cost, 2) }}</td>
        <td><span class="badge">{{ $t->status }}</span></td>
        <td>{{ $t->created_at->format('d/m/Y') }}</td>
        <td class="actions">
          <a href="{{ route('treatments.edit', $t) }}" class="btn small secondary">Edit</a>
          <form method="POST" action="{{ route('treatments.destroy', $t) }}" onsubmit="return confirm('Delete this treatment? Its invoice line will be removed too.');">
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
