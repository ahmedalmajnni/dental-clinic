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
    <span class="brand">🦷 Dental Clinic</span>
    <a href="{{ route('login') }}">Log in</a>
    <a href="{{ route('signup') }}" class="btn">Sign up as a patient</a>
    <a href="{{ route('staff-register.create') }}">Staff access</a>
  </nav>

  @yield('content')

  <footer class="site-footer">
    <p>🦷 Dental Clinic — appointments, treatment records and billing, all in one place.</p>
    <p>
      Already registered? <a href="{{ route('login') }}">Log in</a> ·
      Joining the team? <a href="{{ route('staff-register.create') }}">Request staff access</a>
    </p>
  </footer>
</body>
</html>
