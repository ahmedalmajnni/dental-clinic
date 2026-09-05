@extends('layouts.app')
@php($isEdit = $specialty->exists)
@section('title', $isEdit ? 'Edit specialty' : 'New specialty')
@section('content')
<div style="min-height:calc(100vh - 170px); display:grid; place-items:center;">
  <div class="card" style="width:100%; max-width:520px;">
    <h1>{{ $isEdit ? 'Edit specialty' : 'New specialty' }}</h1>
    <form method="POST" action="{{ $action }}">
      @csrf
      @if($method === 'PUT') @method('PUT') @endif

      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="{{ old('name', $specialty->name) }}" maxlength="120" required autofocus>

      <div class="actions" style="margin-top:18px;">
        <button type="submit" class="btn">Save</button>
        <a href="{{ route('specialties.index') }}" class="btn secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
