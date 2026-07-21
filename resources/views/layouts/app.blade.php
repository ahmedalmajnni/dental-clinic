<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Dental Clinic') · Dental Clinic</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
  @auth
    @php($u = auth()->user())
    <nav class="topbar">
      <span class="brand">🦷 Dental Clinic</span>
      <a href="{{ route('dashboard') }}">Dashboard</a>

      @if(in_array($u->role, ['admin','employee']))
        <a href="{{ route('patients.index') }}">Patients</a>
        <a href="{{ route('appointments.index') }}">Appointments</a>
        <a href="{{ route('requests.index') }}">Requests</a>
        <a href="{{ route('treatments.index') }}">Treatments</a>
        <a href="{{ route('lab-cases.index') }}">Lab</a>
        <a href="{{ route('media.index') }}">Media</a>
        <a href="{{ route('invoices.index') }}">Invoices</a>
        <a href="{{ route('payments.index') }}">Payments</a>
      @endif

      @if($u->role === 'admin')
        <a href="{{ route('branches.index') }}">Branches</a>
        <a href="{{ route('employees.index') }}">Employees</a>
        <a href="{{ route('accounts.index') }}">Accounts</a>
        <a href="{{ route('accounts.requests') }}">Staff requests</a>
      @endif

      @if($u->role === 'patient')
        <a href="{{ route('appointment-request.create') }}">Request appointment</a>
        <a href="{{ route('my-requests') }}">My requests</a>
      @endif

      <span class="spacer"></span>
      <span class="who">{{ $u->name }} ({{ $u->role }})</span>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="link">Log out</button>
      </form>
    </nav>
  @endauth
  <main>
    @if(session('flash'))
      <div class="flash {{ session('flash')['type'] }}">{{ session('flash')['message'] }}</div>
    @endif
    @yield('content')
  </main>
</body>
</html>
