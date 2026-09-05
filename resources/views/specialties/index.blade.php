@extends('layouts.app')
@section('title', 'Specialties')
@section('content')
<div class="toolbar">
  <h1>Specialties</h1>
  <a href="{{ route('specialties.create') }}" class="btn">+ New specialty</a>
</div>
@if($specialties->isEmpty())
  <div class="card"><p class="muted">No specialties yet. Create your first one.</p></div>
@else
<table>
  <thead><tr><th>Name</th><th></th></tr></thead>
  <tbody>
    @foreach($specialties as $specialty)
      <tr>
        <td>{{ $specialty->name }}</td>
        <td class="actions">
          <a href="{{ route('specialties.edit', $specialty) }}" class="btn small secondary edit-action">Edit</a>
          <form method="POST" action="{{ route('specialties.destroy', $specialty) }}" onsubmit="return confirm('Delete this specialty?');">
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
