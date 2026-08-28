@extends('layouts.public')
@section('title', 'Dental Clinic · Book an appointment')
@section('content')

<section class="hero">
  <div class="hero-inner">
    <h1>Dental care you can book from your own account</h1>
    <p class="lede">
      Ask for a visit with the dentist and clinic you prefer. Our reception team confirms the
      time, and from then on your appointments, treatments and invoices all live in one place.
    </p>
    <div class="cta-row">
      <a href="{{ route('signup') }}" class="btn solid">Request an appointment</a>
      <a href="{{ route('login') }}" class="btn ghost">Log in</a>
    </div>
  </div>
</section>

<section class="section">
  <h2>The clinic in numbers</h2>
  <p class="section-sub">Figures taken straight from our own records.</p>
  <div class="figure-strip">
    <div class="figure">
      <div class="n">{{ number_format($stats['patients']) }}</div>
      <div class="l">Patients cared for</div>
    </div>
    <div class="figure">
      <div class="n">{{ number_format($stats['doctors']) }}</div>
      <div class="l">Dentists</div>
    </div>
    <div class="figure">
      <div class="n">{{ number_format($stats['branches']) }}</div>
      <div class="l">Clinics</div>
    </div>
    <div class="figure">
      <div class="n">{{ number_format($stats['treatments']) }}</div>
      <div class="l">Treatments completed</div>
    </div>
  </div>
</section>

@php
  // The service list is drawn from procedures we have actually performed, so a brand
  // new database would show nothing at all — fall back to the standard menu of care.
  $serviceNames = $services->isEmpty()
    ? collect(['Check-up & cleaning', 'Fillings', 'Crowns & bridges', 'Root canal',
               'Extractions', 'Implants', 'Orthodontics', 'Teeth whitening'])
    : $services;

  // Procedure names are free text, so the icon is matched on a keyword rather than looked up.
  $serviceIcons = [
    'clean' => '🪥', 'scal' => '🪥', 'polish' => '🪥',
    'check' => '🩺', 'exam' => '🩺', 'consult' => '🩺',
    'fill' => '🧱', 'crown' => '👑', 'bridge' => '👑', 'veneer' => '💎',
    'root' => '🌱', 'extract' => '🩹', 'implant' => '🔩',
    'ortho' => '🪛', 'brace' => '🪛', 'align' => '🪛',
    'whiten' => '✨', 'bleach' => '✨', 'denture' => '😁',
    'x-ray' => '🩻', 'xray' => '🩻', 'scan' => '🩻',
  ];

  $serviceTiles = $serviceNames->map(function ($name) use ($serviceIcons) {
    $ico = '🦷';
    foreach ($serviceIcons as $keyword => $emoji) {
      if (str_contains(strtolower($name), $keyword)) {
        $ico = $emoji;
        break;
      }
    }
    return ['name' => $name, 'ico' => $ico];
  });
@endphp

<div class="section-band">
  <section class="section">
    <h2>What we do</h2>
    <p class="section-sub">Everyday dentistry and the bigger work, under one roof.</p>
    <div class="tile-grid">
      @foreach($serviceTiles as $tile)
        <div class="tile">
          <span class="ico">{{ $tile['ico'] }}</span>
          <h3>{{ $tile['name'] }}</h3>
        </div>
      @endforeach
    </div>
  </section>
</div>

<section class="section">
  <h2>How it works</h2>
  <p class="section-sub">Three steps from first visit to a confirmed chair time.</p>
  <div class="steps">
    <div class="step">
      <h3>Create an account</h3>
      <p>Sign up as a patient with your name, phone and email. It takes a minute and you only do it once.</p>
    </div>
    <div class="step">
      <h3>Request an appointment</h3>
      <p>Pick the dentist and the clinic you want to be seen at, say when suits you, and add a note about what is bothering you.</p>
    </div>
    <div class="step">
      <h3>We confirm your time</h3>
      <p>Reception schedules the visit and the confirmed date and time appear in your account, where you can follow every request you have made.</p>
    </div>
  </div>
</section>

{{-- Clinics and dentists are real records: drop the whole section rather than show an empty grid. --}}
@if($branches->isNotEmpty())
  <div class="section-band">
    <section class="section">
      <h2>Our clinics</h2>
      <p class="section-sub">Choose the one nearest to you when you request your appointment.</p>
      <div class="tile-grid">
        @foreach($branches as $b)
          <div class="tile">
            <span class="ico">📍</span>
            <h3>{{ $b->name }}</h3>
            <p>
              {{ $b->address ?: 'Address given when we confirm your visit' }}
              @if($b->phone)
                <br>☎ {{ $b->phone }}
              @endif
            </p>
          </div>
        @endforeach
      </div>
    </section>
  </div>
@endif

@if($doctors->isNotEmpty())
  <section class="section">
    <h2>Our dentists</h2>
    <p class="section-sub">You choose who treats you — request them by name.</p>
    <div class="tile-grid">
      @foreach($doctors as $d)
        <div class="tile">
          <span class="ico">🧑‍⚕️</span>
          <h3>{{ $d->name }}</h3>
          <p>Dentist{{ $d->branch?->name ? " · ".$d->branch->name : "" }}</p>
        </div>
      @endforeach
    </div>
  </section>
@endif

<section class="hero">
  <div class="hero-inner">
    <h2>Ready when you are</h2>
    <p class="lede">Creating a patient account is all it takes to ask for your first visit.</p>
    <div class="cta-row">
      <a href="{{ route('signup') }}" class="btn solid">Sign up as a patient</a>
    </div>
  </div>
</section>

@endsection
