@extends('layouts.app')
@section('title', $patient->name.' — Patient history')
@section('content')
<div class="toolbar">
  <h1>{{ $patient->name }}</h1>
  <div class="actions">
    <a href="{{ route('patients.edit', $patient) }}" class="btn secondary">Edit patient</a>
    <a href="{{ route('patients.index') }}" class="btn secondary">Back</a>
  </div>
</div>

<div class="card" style="margin-bottom:18px;">
  <strong>Patient details</strong>
  <p class="muted" style="margin-bottom:0;">
    DOB: {{ $patient->dob?->format('d/m/Y') ?: '—' }} ·
    Phone: {{ $patient->phone ?: '—' }} ·
    Email: {{ $patient->email ?: '—' }}
  </p>
</div>

<h2>Appointments</h2>
@if($appointments->isEmpty())
  <p class="muted">No appointments.</p>
@else
<table>
  <thead><tr><th>When</th><th>Doctor</th><th>Status</th></tr></thead>
  <tbody>
    @foreach($appointments as $appointment)
      <tr>
        <td>{{ $appointment->scheduled_at->format('d/m/Y h:i A') }}</td>
        <td>{{ $appointment->doctor->name }}</td>
        <td>{{ $appointment->status }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

<h2>Clinical records</h2>
@if($treatments->isEmpty() && $reports->isEmpty())
  <p class="muted">No clinical records.</p>
@else
<table>
  <thead><tr><th>Type</th><th>Details</th><th>Date</th></tr></thead>
  <tbody>
    @foreach($treatments as $treatment)
      <tr>
        <td>Treatment</td>
        <td>{{ $treatment->procedure }} ({{ number_format((float) $treatment->cost, 2) }})</td>
        <td>{{ $treatment->created_at?->format('d/m/Y') ?: '—' }}</td>
      </tr>
    @endforeach
    @foreach($reports as $report)
      <tr>
        <td>Report</td>
        <td>{{ $report->diagnosis ?: $report->notes ?: '—' }}{{ $report->next_visit ? ' · Next visit: '.$report->next_visit->format('d/m/Y') : '' }}</td>
        <td>{{ $report->created_at?->format('d/m/Y') ?: '—' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

<h2>Lab cases and media</h2>
@if($labCases->isEmpty() && $media->isEmpty())
  <p class="muted">No lab cases or media.</p>
@else
<table>
  <thead><tr><th>Type</th><th>Details</th><th>Status/date</th></tr></thead>
  <tbody>
    @foreach($labCases as $labCase)
      <tr>
        <td>Lab case</td>
        <td>{{ $labCase->type }} · Dr {{ $labCase->doctor->name }}</td>
        <td>{{ str_replace('_', ' ', $labCase->status) }}{{ $labCase->due_date ? ' · '.$labCase->due_date->format('d/m/Y') : '' }}</td>
      </tr>
    @endforeach
    @foreach($media as $item)
      <tr>
        <td>Media</td>
        <td><a href="{{ $item->file_url }}" target="_blank" rel="noopener">{{ $item->type }}{{ $item->category ? ' · '.$item->category : '' }}</a></td>
        <td>{{ $item->taken_at?->format('d/m/Y') ?: '—' }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
@endif

@endsection
