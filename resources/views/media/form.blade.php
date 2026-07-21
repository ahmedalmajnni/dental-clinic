@extends('layouts.app')
@php($isEdit = $item->exists)
@section('title', $isEdit ? 'Edit media' : 'New media')
@section('content')
<div class="card" style="max-width:560px;">
  <h1>{{ $isEdit ? 'Edit media' : 'New media' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="patient_id">Patient</label>
    <select id="patient_id" name="patient_id" required>
      <option value="">— choose patient —</option>
      @foreach($patients as $p)
        <option value="{{ $p->id }}" @selected(old('patient_id', $item->patient_id) === $p->id)>{{ $p->name }}</option>
      @endforeach
    </select>

    <label for="type">Type</label>
    <select id="type" name="type" required>
      @foreach($types as $t)
        <option value="{{ $t }}" @selected(old('type', $item->type) === $t)>{{ $t }}</option>
      @endforeach
    </select>

    <label for="category">Category (optional)</label>
    <input type="text" id="category" name="category" maxlength="40"
           value="{{ old('category', $item->category) }}" placeholder="e.g. before, after, intraoral">

    <label for="branch_id">Taken at branch (optional)</label>
    <select id="branch_id" name="branch_id">
      <option value="">— not specified —</option>
      @foreach($branches as $b)
        <option value="{{ $b->id }}" @selected(old('branch_id', $item->branch_id) === $b->id)>{{ $b->name }}</option>
      @endforeach
    </select>

    <label for="file_url">File link (URL)</label>
    <input type="url" id="file_url" name="file_url" required
           value="{{ old('file_url', $item->file_url) }}" placeholder="https://storage.example.com/xray-123.jpg">
    <p class="muted">Paste the link to where the image or scan is stored. The file itself lives in storage; we keep the link.</p>

    <label for="taken_at">Date taken (optional)</label>
    <input type="date" id="taken_at" name="taken_at" value="{{ old('taken_at', optional($item->taken_at)->format('Y-m-d')) }}">

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('media.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
