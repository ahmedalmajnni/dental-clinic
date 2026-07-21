@extends('layouts.app')
@php($isEdit = $branch->exists)
@section('title', $isEdit ? 'Edit branch' : 'New branch')
@section('content')
<div class="card" style="max-width:520px;">
  <h1>{{ $isEdit ? 'Edit branch' : 'New branch' }}</h1>
  <form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $branch->name) }}" required autofocus>

    <label for="type">Type</label>
    <select id="type" name="type">
      <option value="clinic" @selected(old('type', $branch->type) === 'clinic')>Clinic</option>
      <option value="studio" @selected(old('type', $branch->type) === 'studio')>Studio</option>
    </select>

    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}">

    <label for="address">Address</label>
    <input type="text" id="address" name="address" value="{{ old('address', $branch->address) }}">

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Save</button>
      <a href="{{ route('branches.index') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
