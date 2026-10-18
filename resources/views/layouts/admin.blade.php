<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>@yield('title', __('Admin')) | {{ __('Himma') }}</title>
  @include('partials.theme-init')
  @include('partials.head')
  <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/notifications.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/admin.css') }}">
</head>
<body class="admin">
@php
  $paymentsToReview = \App\Models\Subscription::where('status', 'payment_review')->count();
  $coachesPending   = \App\Models\CoachProfile::where('status', 'pending')->count();
  $unreadMessages   = \App\Models\ContactMessage::where('is_read', false)->count();
  $adminLinks = [
      ['admin.dashboard',           'admin.dashboard',       'fa-gauge-high',        __('Dashboard'),     0],
      ['admin.subscriptions.index', 'admin.subscriptions.*', 'fa-receipt',           __('Payments'),      $paymentsToReview],
      ['admin.coaches.index',       'admin.coaches.*',       'fa-user-tie',          __('Coaches'),       $coachesPending],
      ['admin.payouts.index',       'admin.payouts.*',       'fa-hand-holding-dollar', __('Coach payouts'), 0],
      ['admin.users.index',         'admin.users.*',         'fa-users',             __('Users'),         0],
      ['admin.exercises.index',     'admin.exercises.*',     'fa-dumbbell',          __('Exercises'),     0],
      ['admin.foods.index',         'admin.foods.*',         'fa-apple-whole',       __('Foods'),         0],
      ['admin.templates.index',     'admin.templates.*',     'fa-file-lines',        __('Templates'),     0],
      ['admin.messages.index',      'admin.messages.*',      'fa-inbox',             __('Messages'),      $unreadMessages],
      ['admin.settings.edit',       'admin.settings.*',      'fa-gear',              __('Settings'),      0],
      ['admin.system.index',        'admin.system.*',        'fa-shield-halved',     __('System'),        0],
  ];
@endphp
<div class="app">
  <aside class="side" id="admin-side">
    <a href="{{ route('admin.dashboard') }}" class="brand">
      <span class="brand-mark">@include('partials.logo-mark')</span>
      <span class="brand-name">{{ __('Himma') }}<small>{{ __('Admin Panel') }}</small></span>
    </a>
    <nav class="side-nav">
      @foreach($adminLinks as [$route, $pattern, $icon, $label, $badge])
        <a href="{{ route($route) }}" class="side-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
          <i class="fas {{ $icon }}"></i><span class="grow">{{ $label }}</span>
          @if($badge)<b class="side-count">{{ $badge }}</b>@endif
        </a>
      @endforeach
    </nav>
    <div class="side-foot">
      @include('partials.lang-switch', ['class' => 'side-link'])
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="side-link"><i class="fas fa-arrow-right-from-bracket"></i>{{ __('Logout') }}</button>
      </form>
    </div>
  </aside>
  <div class="side-backdrop" data-side-close></div>

  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn admin-menu" data-side-toggle aria-controls="admin-side" aria-label="{{ __('Menu') }}"><i class="fas fa-bars"></i></button>
      <div class="topbar-title">@yield('page-title', __('Dashboard'))</div>
      <div class="topbar-actions">
        @include('partials.notification-bell')
        <button type="button" class="icon-btn" data-theme-toggle title="{{ __('Day / night') }}" aria-label="{{ __('Day / night') }}">
          <i class="fas fa-moon theme-light-icon"></i><i class="fas fa-sun theme-dark-icon"></i>
        </button>
      </div>
    </header>

    <main class="content">
      @foreach(['status' => 'circle-check', 'success' => 'circle-check', 'error' => 'circle-exclamation'] as $flash => $icon)
        @if(session($flash))
          <div class="alert {{ $flash === 'error' ? 'alert-error' : 'alert-success' }}" role="status">
            <i class="fas fa-{{ $icon }}"></i><div>{{ session($flash) }}</div>
          </div>
        @endif
      @endforeach

      @yield('content')
    </main>
  </div>
</div>

<script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
<script src="{{ \App\Support\Asset::url('js/admin.js') }}" defer></script>
</body>
</html>
