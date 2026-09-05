@extends('layouts.app')
@section('title', 'Media')
@section('content')
<div class="toolbar">
  <h1>Media</h1>
  <a href="{{ route('media.create') }}" class="btn">+ Add media</a>
</div>
@if($media->isEmpty())
  <div class="card"><p class="muted">No media yet. Add x-rays, scans, or photos by linking to where the file is stored.</p></div>
@else
<table>
  <thead><tr><th>Taken</th><th>Patient</th><th>Type</th><th>Category</th><th>File</th><th>Cost</th><th></th></tr></thead>
  <tbody>
    @foreach($media as $m)
      <tr>
        <td>{{ $m->taken_at->format('d/m/Y') }}</td>
        <td>{{ $m->patient->name }}</td>
        <td><span class="badge">{{ $m->type }}</span></td>
        <td>{{ $m->category ?: '—' }}</td>
        <td><a href="{{ $m->file_url }}" target="_blank" rel="noopener noreferrer">Open ↗</a></td>
        <td>${{ number_format((float) $m->cost, 2) }}</td>
        <td class="actions">
          <a href="{{ route('media.edit', $m) }}" class="btn small secondary edit-action">Edit</a>
          <form method="POST" action="{{ route('media.destroy', $m) }}" onsubmit="return confirm('Delete this media record?');">
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
