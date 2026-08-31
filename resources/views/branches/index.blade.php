@extends('layouts.app')
@section('title', 'Branches')
@section('content')
<div class="toolbar">
  <h1>Branches</h1>
  <a href="{{ route('branches.create') }}" class="btn">+ New branch</a>
</div>
@if($branches->isEmpty())
  <div class="card"><p class="muted">No branches yet. Create your first one.</p></div>
@else
<table>
  <thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Address</th><th></th></tr></thead>
  <tbody>
    @foreach($branches as $b)
      <tr>
        <td>{{ $b->name }}</td>
        <td><span class="badge">{{ $b->type }}</span></td>
        <td>{{ $b->phone ?: '—' }}</td>
        <td>{{ $b->address ?: '—' }}</td>
        <td class="actions">
          <a href="{{ route('branches.edit', $b) }}" class="btn small secondary edit-action">Edit</a>
          <form method="POST" action="{{ route('branches.destroy', $b) }}" onsubmit="return confirm('Delete this branch?');">
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
