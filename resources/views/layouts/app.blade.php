<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'DentFlow') · DentFlow</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}?v=7">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body>
  @auth
    @php
      $u = auth()->user();
      $isAdmin = $u->role === 'admin';
      $isStaff = in_array($u->role, ['admin', 'employee'], true);
      $seesAvailability = $isAdmin || optional($u->employee)->job_title === 'doctor';

      // Which dropdown to highlight: matched on the current route name's prefix,
      // so every sub-page (create/edit/process) lights up its own section too.
      $routeName = Route::currentRouteName() ?? '';
      $sections = [
        'patients' => ['patients.'],
        'appointments' => ['appointments.', 'requests.', 'availability.'],
        'clinical' => ['treatments.', 'lab-cases.', 'media.'],
        'admin' => ['specialties.', 'employees.', 'accounts.'],
      ];
      $active = '';
      foreach ($sections as $key => $prefixes) {
          foreach ($prefixes as $prefix) {
              if (str_starts_with($routeName, $prefix)) {
                  $active = $key;
                  break 2;
              }
          }
      }
    @endphp

    <nav class="topbar">
      <a class="brand" href="{{ route('dashboard') }}"><img src="{{ asset('images/logo.svg') }}" alt=""> <span>DentFlow</span></a>

      <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="mainnav" aria-label="Toggle menu">☰</button>

      <div class="nav-links" id="mainnav">
        <a class="nav-item {{ $routeName === 'dashboard' ? 'on' : '' }}" href="{{ route('dashboard') }}">Home</a>

        @if($isStaff)
          <div class="nav-group">
            <button type="button" class="nav-trigger {{ $active === 'patients' ? 'on' : '' }}" aria-expanded="false" aria-haspopup="true">
              <span class="ico">🧑</span> Patients <span class="caret" aria-hidden="true">▾</span>
            </button>
            <div class="nav-menu" role="menu">
              <a role="menuitem" href="{{ route('patients.index') }}">All patients</a>
              <a role="menuitem" href="{{ route('patients.archived') }}">Archived patients</a>
            </div>
          </div>

          <div class="nav-group">
            <button type="button" class="nav-trigger {{ $active === 'appointments' ? 'on' : '' }}" aria-expanded="false" aria-haspopup="true">
              <span class="ico">📅</span> Appointments <span class="caret" aria-hidden="true">▾</span>
            </button>
            <div class="nav-menu" role="menu">
              <a role="menuitem" href="{{ route('appointments.index') }}">Appointments</a>
              <a role="menuitem" href="{{ route('requests.index') }}">Requests</a>
              @if($seesAvailability)
                <a role="menuitem" href="{{ route('availability.index') }}">Availability</a>
              @endif
            </div>
          </div>

          <div class="nav-group">
            <button type="button" class="nav-trigger {{ $active === 'clinical' ? 'on' : '' }}" aria-expanded="false" aria-haspopup="true">
              <img class="nav-logo-icon" src="{{ asset('images/logo.svg') }}" alt=""> Clinical <span class="caret" aria-hidden="true">▾</span>
            </button>
            <div class="nav-menu" role="menu">
              <a role="menuitem" href="{{ route('treatments.index') }}">Treatments</a>
              <a role="menuitem" href="{{ route('lab-cases.index') }}">Lab cases</a>
              <a role="menuitem" href="{{ route('media.index') }}">Media</a>
            </div>
          </div>

        @endif

        @if($isAdmin)
          <div class="nav-group">
            <button type="button" class="nav-trigger {{ $active === 'admin' ? 'on' : '' }}" aria-expanded="false" aria-haspopup="true">
              <span class="ico">⚙️</span> Management <span class="caret" aria-hidden="true">▾</span>
            </button>
            <div class="nav-menu" role="menu">
              <a role="menuitem" href="{{ route('specialties.index') }}">Specialties</a>
              <a role="menuitem" href="{{ route('accounts.index') }}">Accounts</a>
            </div>
          </div>
        @endif

        @if($u->role === 'patient')
          <a class="nav-item {{ $routeName === 'appointment-request.create' ? 'on' : '' }}" href="{{ route('appointment-request.create') }}">Request appointment</a>
          <a class="nav-item {{ str_starts_with($routeName, 'my-requests') ? 'on' : '' }}" href="{{ route('my-requests') }}">My requests</a>
        @endif

        {{-- Secondary, so it sits after the day-to-day sections rather than competing with them. --}}
        <a class="nav-item {{ $routeName === 'about' ? 'on' : '' }}" href="{{ route('about') }}">About us</a>
      </div>

      @php
        // "Doctor" / "Reception" reads better than the raw role for staff; a
        // patient has no job title, so the role itself is the status.
        $jobTitle = optional($u->employee)->job_title;
        $statusLabel = $u->role === 'patient'
            ? 'Patient'
            : ucfirst(str_replace('_', ' ', $jobTitle ?: $u->role));
        $initials = collect(explode(' ', trim($u->name ?? '')))
            ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
      @endphp

      <div class="nav-user nav-group">
        <button type="button" class="nav-trigger user-trigger" aria-expanded="false" aria-haspopup="true">
          <span class="avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
          <span class="user-name">{{ $u->name }}</span>
          <span class="caret" aria-hidden="true">▾</span>
        </button>
        <div class="nav-menu nav-menu-right" role="menu">
          <div class="menu-head">
            <div class="menu-name">{{ $u->name }}</div>
            <div class="menu-mail">{{ $u->email }}</div>
            <span class="badge job">{{ $statusLabel }}</span>
          </div>
          <a role="menuitem" href="{{ route('profile.edit') }}">My account &amp; details</a>
          <div class="menu-sep"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" role="menuitem" class="menu-btn">Log out</button>
          </form>
        </div>
      </div>
    </nav>

    <script>
      // Dropdowns open on hover via CSS alone; this adds click and keyboard
      // control, which hover cannot provide.
      (function () {
        var groups = [].slice.call(document.querySelectorAll('.nav-group'));

        function close(group) {
          group.classList.remove('open');
          group.querySelector('.nav-trigger').setAttribute('aria-expanded', 'false');
        }
        function closeAll(except) {
          groups.forEach(function (g) { if (g !== except) { close(g); } });
        }

        groups.forEach(function (group) {
          var trigger = group.querySelector('.nav-trigger');
          var items = [].slice.call(group.querySelectorAll('.nav-menu a'));

          trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = group.classList.toggle('open');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            closeAll(group);
          });

          // Down-arrow from the trigger jumps into the menu.
          trigger.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' && items.length) {
              e.preventDefault();
              group.classList.add('open');
              trigger.setAttribute('aria-expanded', 'true');
              closeAll(group);
              items[0].focus();
            }
          });

          group.addEventListener('keydown', function (e) {
            var i = items.indexOf(document.activeElement);
            if (e.key === 'Escape') {
              close(group);
              trigger.focus();
            } else if (e.key === 'ArrowDown' && i > -1) {
              e.preventDefault();
              (items[i + 1] || items[0]).focus();
            } else if (e.key === 'ArrowUp' && i > -1) {
              e.preventDefault();
              (items[i - 1] || items[items.length - 1]).focus();
            }
          });

          // Tabbing out of the group closes it.
          group.addEventListener('focusout', function () {
            setTimeout(function () {
              if (!group.contains(document.activeElement)) { close(group); }
            }, 0);
          });
        });

        document.addEventListener('click', function () { closeAll(null); });

        var toggle = document.querySelector('.nav-toggle');
        var links = document.getElementById('mainnav');
        if (toggle && links) {
          toggle.addEventListener('click', function () {
            var open = links.classList.toggle('show');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
          });
        }
      })();
    </script>
  @endauth
  <main>
    @if(session('flash'))
      <div class="flash {{ session('flash')['type'] }}">{{ session('flash')['message'] }}</div>
    @endif
    @yield('content')
  </main>
  <script>
    document.querySelectorAll('input[type="password"]').forEach(function (input) {
      if (input.parentElement.classList.contains('password-wrap')) return;
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
</body>
</html>
