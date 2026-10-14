<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>@yield('title') | {{ __('Ameen Gym') }}</title>
  @include('partials.theme-init')
  @include('partials.head')
  <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
  @stack('styles')
</head>
<body>
@php
  $me = auth()->user();
  $role = $me?->role;
  // [route, icon, label, also-active pattern]
  $links = match ($role) {
      'trainee' => [
          ['trainee.dashboard', 'fa-house', __('Home'), 'trainee.*'],
          ['coaches.index', 'fa-users', __('Coaches'), 'coaches.*'],
          ['exercises.index', 'fa-dumbbell', __('Exercises'), 'exercises.*'],
      ],
      'trainer' => [
          ['trainer.dashboard', 'fa-house', __('Home'), 'coach.clients.*'],
          ['coach.packages.index', 'fa-tags', __('My Packages'), 'coach.packages.*'],
          ['coach.profile.edit', 'fa-id-badge', __('My Profile'), 'coach.profile.*'],
          ['exercises.index', 'fa-dumbbell', __('Exercises'), 'exercises.*'],
      ],
      'admin' => [
          ['admin.dashboard', 'fa-gauge-high', __('Admin Panel'), 'admin.*'],
          ['coaches.index', 'fa-users', __('Coaches'), 'coaches.*'],
      ],
      default => [
          ['home', 'fa-house', __('Home'), 'home'],
          ['coaches.index', 'fa-users', __('Coaches'), 'coaches.*'],
      ],
  };
  $isActive = fn ($route, $pattern) => request()->routeIs($route) || request()->routeIs($pattern);
@endphp
<div class="app">
  <aside class="side">
    <a href="{{ route('home') }}" class="brand">
      <span class="brand-mark"><i class="fas fa-dumbbell"></i></span>
      <span class="brand-name">{{ __('Ameen Gym') }}<small>{{ $role === 'trainer' ? __('Coach area') : ($role === 'trainee' ? __('Trainee area') : __('Online coaching')) }}</small></span>
    </a>
    <nav class="side-nav">
      @foreach($links as [$route, $icon, $label, $pattern])
        <a href="{{ route($route) }}" class="side-link {{ $isActive($route, $pattern) ? 'active' : '' }}"><i class="fas {{ $icon }}"></i>{{ $label }}</a>
      @endforeach
      @auth
        <a href="{{ route('notifications.index') }}" class="side-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}"><i class="fas fa-bell"></i>{{ __('Notifications') }}</a>
      @endauth
    </nav>
    <div class="side-foot">
      <button type="button" class="side-link" data-install-app hidden><i class="fas fa-download"></i>{{ __('Install App') }}</button>
      @include('partials.lang-switch', ['class' => 'side-link'])
      @auth
        <form action="{{ route('logout') }}" method="POST">
          @csrf
          <button type="submit" class="side-link"><i class="fas fa-arrow-right-from-bracket"></i>{{ __('Logout') }}</button>
        </form>
      @else
        <a href="{{ route('login.form') }}" class="btn btn-primary btn-block"><i class="fas fa-right-to-bracket"></i> {{ __('Login') }}</a>
      @endauth
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <a href="{{ route('home') }}" class="brand" aria-label="{{ __('Ameen Gym') }}">
        <span class="brand-mark"><i class="fas fa-dumbbell"></i></span>
      </a>
      <div class="topbar-title">@yield('title')</div>
      <div class="topbar-actions">
        @include('partials.notification-bell')
        <button type="button" class="icon-btn" data-theme-toggle title="{{ __('Day / night') }}" aria-label="{{ __('Day / night') }}">
          <i class="fas fa-moon theme-light-icon"></i><i class="fas fa-sun theme-dark-icon"></i>
        </button>
        @guest
          <a href="{{ route('login.form') }}" class="btn btn-primary btn-sm">{{ __('Login') }}</a>
        @endguest
      </div>
    </header>

    <main class="content">
      @foreach(['success' => 'circle-check', 'status' => 'circle-check', 'error' => 'circle-exclamation'] as $flash => $icon)
        @if(session($flash))
          <div class="alert {{ $flash === 'error' ? 'alert-error' : 'alert-success' }}" role="status">
            <i class="fas fa-{{ $icon }}"></i><div>{{ session($flash) }}</div>
          </div>
        @endif
      @endforeach

      @if($errors->any())
        <div class="alert alert-error" role="alert">
          <i class="fas fa-circle-exclamation"></i>
          <div>
            @if($errors->count() === 1)
              {{ $errors->first() }}
            @else
              <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
          </div>
        </div>
      @endif

      @yield('content')
    </main>
  </div>

  <nav class="tabbar" aria-label="{{ __('Menu') }}">
    @foreach($links as [$route, $icon, $label, $pattern])
      <a href="{{ route($route) }}" class="tab {{ $isActive($route, $pattern) ? 'active' : '' }}"><i class="fas {{ $icon }}"></i>{{ $label }}</a>
    @endforeach
    @auth
      <a href="{{ route('notifications.index') }}" class="tab {{ request()->routeIs('notifications.*') ? 'active' : '' }}"><i class="fas fa-bell"></i>{{ __('Alerts') }}</a>
    @else
      <a href="{{ route('login.form') }}" class="tab"><i class="fas fa-right-to-bracket"></i>{{ __('Login') }}</a>
    @endauth
  </nav>
</div>

<script src="{{ asset('js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
