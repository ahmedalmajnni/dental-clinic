<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'DentFlow')</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}?v=7">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body>
  {{-- Guest shell: no flash block and no app nav, since nothing here is signed in. --}}
  <nav class="landing-nav">
    <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.svg') }}" alt=""> <span>DentFlow</span></a>
    <a href="{{ route('about') }}">About us</a>
    <a href="{{ route('login') }}">Log in</a>
    <a href="{{ route('signup') }}" class="btn">Sign up as a patient</a>
  </nav>

  @yield('content')

  <script>
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
      var wrapper = document.createElement('div');
      wrapper.className = 'password-wrap';
      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(input);

      var label = document.createElement('label');
      label.className = 'password-visibility';
      var checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.setAttribute('aria-label', 'Show password');
      checkbox.addEventListener('change', function () {
        input.type = checkbox.checked ? 'text' : 'password';
      });
      label.appendChild(checkbox);
      label.appendChild(document.createTextNode(' Show password'));
      wrapper.appendChild(label);
    });
  </script>

  <footer class="site-footer">
    <p><img class="inline-logo" src="{{ asset('images/logo.svg') }}" alt=""> DentFlow — appointments, treatment records and billing, all in one place.</p>
    <p>
      <a href="{{ route('about') }}">About us</a> ·
      Already registered? <a href="{{ route('login') }}">Log in</a>
    </p>
  </footer>
</body>
</html>
