{{-- Visitors get the marketing shell; signed-in staff and patients keep their own
     navigation, so this never offers "Log in" to someone already logged in. --}}
@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'About us')
@section('content')

<section class="hero">
  <div class="hero-inner">
    <h1>About our clinic</h1>
    <p class="lede">
      We are a dental practice running across several clinics, with one shared patient record
      and one system behind it — so wherever you are seen, your history, your treatment plan
      and your bill are the same.
    </p>
  </div>
</section>

<section class="section">
  <h2>What we work in</h2>
  <p class="section-sub">Everyday dentistry through to the longer, more involved work.</p>

  @php
    // Real procedures where we have them; a fresh database gets the standard menu.
    $areas = $services->isEmpty()
      ? collect(['Check-up & cleaning', 'Fillings', 'Crowns & bridges', 'Root canal',
                 'Extractions', 'Implants', 'Orthodontics', 'Teeth whitening'])
      : $services;
  @endphp

  <div class="tile-grid service-grid">
    <div class="tile">
      <span class="ico">🩺</span>
      <h3>General dentistry</h3>
      <p>Examinations, cleaning and fillings — the routine care that keeps bigger problems from starting.</p>
    </div>
    <div class="tile">
      <span class="ico">👑</span>
      <h3>Restorative work</h3>
      <p>Crowns, bridges and dentures, made with our own lab and tracked from impression to fitting.</p>
    </div>
    <div class="tile">
      <span class="ico">🌱</span>
      <h3>Endodontics &amp; surgery</h3>
      <p>Root canal treatment, extractions and implants, handled by the dentist you chose.</p>
    </div>
    <div class="tile">
      <span class="ico">✨</span>
      <h3>Cosmetic &amp; orthodontics</h3>
      <p>Whitening, veneers and alignment for patients who want to change how their teeth look.</p>
    </div>
    <div class="tile">
      <span class="ico">🧸</span>
      <h3>Paediatric dentistry</h3>
      <p>Friendly check-ups, preventive care and gentle treatment to help children build healthy habits early.</p>
    </div>
    <div class="tile">
      <span class="ico">🛡️</span>
      <h3>Preventive care</h3>
      <p>Fluoride guidance, sealants and regular reviews that protect your teeth and catch concerns early.</p>
    </div>
  </div>

  <p class="section-sub" style="margin-top:18px;">
    Procedures we carry out most often: {{ $areas->implode(' · ') }}.
  </p>
</section>

<div class="section-band">
  <section class="section">
    <h2>Where we are</h2>
    <p class="section-sub">Figures and locations taken straight from our own records.</p>
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
        <div class="n">{{ number_format($stats['specialties']) }}</div>
        <div class="l">Specialties</div>
      </div>
      <div class="figure">
        <div class="n">{{ number_format($stats['treatments']) }}</div>
        <div class="l">Treatments completed</div>
      </div>
    </div>
  </section>
</div>

<section class="section">
  <h2>How our system works</h2>
  <p class="section-sub">One record per patient, shared by every clinic and every dentist.</p>
  <div class="tile-grid service-grid">
    <div class="tile">
      <span class="ico">🔑</span>
      <h3>One account each</h3>
      <p>Patients and staff each sign in to their own account and see only what belongs to them. A dentist sees their own list; reception sees the whole clinic.</p>
    </div>
    <div class="tile">
      <span class="ico">📅</span>
      <h3>Request, then confirm</h3>
      <p>You ask for a visit with the dentist you want. We confirm a real time against that dentist's working hours, and it appears in your account with our reply.</p>
    </div>
    <div class="tile">
      <span class="ico">🕒</span>
      <h3>Hours set by each dentist</h3>
      <p>Every dentist keeps their own weekly hours, days off and extra sessions. Only genuinely free times can be booked, so a slot is never promised twice.</p>
    </div>
    <div class="tile">
      <span class="ico">📋</span>
      <h3>Notes that stay with you</h3>
      <p>Diagnosis, treatment and follow-up are written against the visit, so the next dentist you see starts with your full history rather than a blank page.</p>
    </div>
    <div class="tile">
      <span class="ico">🔬</span>
      <h3>Lab work tracked</h3>
      <p>Crowns, bridges and dentures are followed from the day they are sent until the day they come back, with a due date we can actually tell you.</p>
    </div>
    <div class="tile">
      <span class="ico">🧾</span>
      <h3>Billing built in</h3>
      <p>Each treatment becomes a line on your invoice as it is recorded. Payments settle the oldest bill first, so what you owe is always current.</p>
    </div>
  </div>
</section>

<div class="section-band">
  <section class="section">
    <h2>How we improve the service</h2>
    <p class="section-sub">The reasons we built it this way rather than on paper.</p>
    <div class="steps about-steps-grid">
      <div class="step">
        <h3>No more double-booked chairs</h3>
        <p>Because each dentist defines their own availability and taken slots disappear straight away, two patients can no longer be given the same time — the commonest cause of a wasted trip.</p>
      </div>
      <div class="step">
        <h3>You are never left guessing</h3>
        <p>Every request you send has a visible status: waiting, confirmed with a time, or declined with a reason from us. Nothing disappears into a notebook behind reception.</p>
      </div>
      <div class="step">
        <h3>Your history travels with you</h3>
        <p>Records stay with you, so seeing a different dentist or specialist does not mean starting your history again.</p>
      </div>
      <div class="step">
        <h3>Bills that add up</h3>
        <p>Charges are created from the treatment itself, never typed in twice, and payments are allocated automatically. You can see what each item was for and what is still open.</p>
      </div>
      <div class="step">
        <h3>Privacy by role</h3>
        <p>Access follows the job. A dentist reaches their own patients' notes, reception handles scheduling and money, and no one sees more than their work requires.</p>
      </div>
      <div class="step">
        <h3>We keep changing it</h3>
        <p>The system is our own, so when something slows a patient or a nurse down we fix that, rather than waiting on an outside supplier.</p>
      </div>
    </div>
  </section>
</div>

@if($specialties->isNotEmpty())
  <section class="section">
    <h2>Our specialties</h2>
    <p class="section-sub">Care areas available from our dental team.</p>
    <div class="tile-grid">
      @foreach($specialties as $specialty)
        <div class="tile">
          <span class="ico">🦷</span>
          <h3>{{ $specialty->name }}</h3>
        </div>
      @endforeach
    </div>
  </section>
@endif

@guest
  <section class="hero">
    <div class="hero-inner">
      <h2>Come and see us</h2>
      <p class="lede">Creating a patient account is all it takes to ask for your first visit.</p>
      <div class="cta-row">
        <a href="{{ route('signup') }}" class="btn solid">Sign up as a patient</a>
        <a href="{{ route('home') }}" class="btn ghost">Back to home</a>
      </div>
    </div>
  </section>
@endguest

@endsection
