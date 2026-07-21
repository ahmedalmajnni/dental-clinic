@extends('layouts.app')
@section('title', 'Access denied')
@section('content')
<div class="card">
  <h1>Access denied</h1>
  <p class="muted">{{ $exception->getMessage() ?: 'You do not have permission to view this page.' }}</p>
  <a href="{{ route('dashboard') }}" class="btn secondary">Back to dashboard</a>
</div>
@endsection
