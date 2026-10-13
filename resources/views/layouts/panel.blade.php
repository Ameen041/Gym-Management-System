<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title') | {{ __('Ameen Gym') }}</title>
  @include('partials.head')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
  <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
  @stack('styles')
</head>
<body>
@php
  $me = auth()->user();
  $role = $me?->role;
  $links = match ($role) {
      'trainee' => [
          ['trainee.dashboard', 'fa-home', __('Dashboard')],
          ['coaches.index', 'fa-users', __('Coaches')],
          ['exercises.index', 'fa-dumbbell', __('Exercises')],
      ],
      'trainer' => [
          ['trainer.dashboard', 'fa-home', __('Dashboard')],
          ['coach.packages.index', 'fa-tags', __('My Packages')],
          ['coach.profile.edit', 'fa-id-card', __('My Profile')],
          ['exercises.index', 'fa-dumbbell', __('Exercises')],
      ],
      'admin' => [
          ['admin.dashboard', 'fa-chart-line', __('Admin Panel')],
          ['coaches.index', 'fa-users', __('Coaches')],
      ],
      default => [
          ['home', 'fa-home', __('Home')],
          ['coaches.index', 'fa-users', __('Coaches')],
      ],
  };
@endphp
<div class="page">
  <header class="topbar">
    <a href="{{ route('home') }}" class="brand">
      <span class="brand-icon"><i class="fas fa-dumbbell"></i></span>
      <span>
        <strong>{{ __('Ameen Gym') }}</strong>
        <small>{{ $role === 'trainer' ? __('Coach area') : ($role === 'trainee' ? __('Trainee area') : __('Online coaching')) }}</small>
      </span>
    </a>

    <nav class="nav">
      @foreach($links as [$route, $icon, $label])
        <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? 'active' : '' }}">
          <i class="fas {{ $icon }}"></i><span>{{ $label }}</span>
        </a>
      @endforeach

      <button type="button" class="btn btn-sm btn-primary" data-install-app hidden>
        <i class="fas fa-download"></i> {{ __('Install App') }}
      </button>

      @include('partials.notification-bell')

      @include('partials.lang-switch', ['class' => 'btn btn-sm btn-light'])

      @auth
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}</button>
        </form>
      @else
        <a href="{{ route('login.form') }}" class="btn btn-sm btn-primary"><i class="fas fa-sign-in-alt"></i> {{ __('Login') }}</a>
      @endauth
    </nav>
  </header>

  @foreach(['success' => 'check-circle', 'status' => 'check-circle', 'error' => 'exclamation-circle'] as $flash => $icon)
    @if(session($flash))
      <div class="alert {{ $flash === 'error' ? 'alert-error' : 'alert-success' }}">
        <i class="fas fa-{{ $icon }}"></i><div>{{ session($flash) }}</div>
      </div>
    @endif
  @endforeach

  @if($errors->any())
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i>
      <div>
        <strong>{{ __('Please fix the following errors:') }}</strong>
        <ul>
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  @yield('content')
</div>

@stack('scripts')
</body>
</html>
