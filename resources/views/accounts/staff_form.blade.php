@extends('layouts.app')
@section('title', 'New staff account')
@section('content')
<div class="toolbar">
  <h1>New staff account</h1>
  <a href="{{ route('accounts.requests') }}" class="btn secondary">← Staff requests</a>
</div>

<div class="card" style="max-width:560px;">
  <p class="muted" style="margin-top:0;">
    Creates the employee and their login in one step. Unlike a self-registration, this account is
    <strong>active immediately</strong> — there is nothing further to approve.
  </p>

  <form method="POST" action="{{ route('accounts.store-staff') }}">
    @csrf

    <label for="name">Full name</label>
    <input type="text" id="name" name="name" required value="{{ old('name') }}">

    <label for="job_title">Job title</label>
    <select id="job_title" name="job_title" required>
      <option value="">— choose job title —</option>
      @foreach($jobTitles as $j)
        <option value="{{ $j }}" @selected(old('job_title') === $j)>{{ ucfirst(str_replace('_', ' ', $j)) }}</option>
      @endforeach
    </select>
    <p class="muted">An <strong>admin</strong> can manage branches, employees and accounts. Everyone else is staff.</p>

    <label for="branch_id">Branch</label>
    <select id="branch_id" name="branch_id" required>
      <option value="">— choose branch —</option>
      @foreach($branches as $b)
        <option value="{{ $b->id }}" @selected(old('branch_id') === $b->id)>{{ $b->name }}</option>
      @endforeach
    </select>

    <label for="phone">Phone (optional)</label>
    <input type="text" id="phone" name="phone" value="{{ old('phone') }}">

    <hr style="margin:22px 0; border:none; border-top:1px solid var(--border);">

    <label for="email">Login email</label>
    <input type="email" id="email" name="email" required value="{{ old('email') }}">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="new-password">
    <p class="muted">At least 6 characters. Tell them what it is — they can change it under My account.</p>

    <div class="actions" style="margin-top:18px;">
      <button type="submit" class="btn">Create account</button>
      <a href="{{ route('accounts.requests') }}" class="btn secondary">Cancel</a>
    </div>
  </form>
</div>
@endsection
