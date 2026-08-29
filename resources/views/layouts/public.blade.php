<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Dental Clinic')</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body>
  {{-- Guest shell: no flash block and no app nav, since nothing here is signed in. --}}
  <nav class="landing-nav">
    <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.svg') }}" alt=""> <span>Dental Clinic</span></a>
    <a href="{{ route('about') }}">About us</a>
    <a href="{{ route('login') }}">Log in</a>
    <a href="{{ route('signup') }}" class="btn">Sign up as a patient</a>
  </nav>

  @yield('content')

  <footer class="site-footer">
    <p>🦷 Dental Clinic — appointments, treatment records and billing, all in one place.</p>
    <p>
      <a href="{{ route('about') }}">About us</a> ·
      Already registered? <a href="{{ route('login') }}">Log in</a>
    </p>
  </footer>
</body>
</html>
