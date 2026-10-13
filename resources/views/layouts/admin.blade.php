<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', __('Admin')) | {{ __('Ameen Gym') }}</title>
  @include('partials.head')
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin">
  <div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo">
          <i class="fas fa-dumbbell"></i>
          <div>
            <h1>{{ __('Ameen Gym') }}</h1>
            <p>{{ __('Admin Panel') }}</p>
          </div>
        </div>
      </div>

      <nav class="sidebar-nav">
        @php
          $paymentsToReview = \App\Models\Subscription::where('status', 'payment_review')->count();
          $coachesPending   = \App\Models\CoachProfile::where('status', 'pending')->count();
          $unreadMessages   = \App\Models\ContactMessage::where('is_read', false)->count();
          $adminLinks = [
              ['admin.dashboard',           'admin.dashboard',       'fa-chart-line',        __('Dashboard'),     0],
              ['admin.subscriptions.index', 'admin.subscriptions.*', 'fa-receipt',           __('Payments'),      $paymentsToReview],
              ['admin.coaches.index',       'admin.coaches.*',       'fa-user-tie',          __('Coaches'),       $coachesPending],
              ['admin.payouts.index',       'admin.payouts.*',       'fa-hand-holding-usd',  __('Coach payouts'), 0],
              ['admin.users.index',         'admin.users.*',         'fa-users',             __('Users'),         0],
              ['admin.exercises.index',     'admin.exercises.*',     'fa-dumbbell',          __('Exercises'),     0],
              ['admin.foods.index',         'admin.foods.*',         'fa-apple-alt',         __('Foods'),         0],
              ['admin.templates.index',     'admin.templates.*',     'fa-file-alt',          __('Templates'),     0],
              ['admin.messages.index',      'admin.messages.*',      'fa-inbox',             __('Messages'),      $unreadMessages],
              ['admin.settings.edit',       'admin.settings.*',      'fa-cog',               __('Settings'),      0],
          ];
        @endphp
        @foreach($adminLinks as [$route, $pattern, $icon, $label, $badge])
          <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}">
            <i class="fas {{ $icon }}"></i>
            <span>{{ $label }}</span>
            @if($badge)
              <span class="badge badge-danger" style="margin-inline-start:auto;">{{ $badge }}</span>
            @endif
          </a>
        @endforeach
      </nav>

      <div class="sidebar-footer">
        <form method="POST" action="{{ url('/logout') }}">
          @csrf
          <button type="submit" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>{{ __('Logout') }}</span>
          </button>
        </form>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Top Header -->
      <header class="top-header">
        <div class="header-left">
          <button class="sidebar-toggle">
            <i class="fas fa-bars"></i>
          </button>
          <h2>@yield('page-title', __('Dashboard'))</h2>
        </div>
        <div class="header-right">
          @include('partials.notification-bell')
          @include('partials.lang-switch', ['class' => 'btn btn-secondary btn-sm'])
          <div class="admin-info">
            <i class="fas fa-user-circle"></i>
            <span>{{ auth()->user()->name ?? __('Admin') }}</span>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="content-area">
        @if(session('status'))
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('status') }}
          </div>
        @endif
        
        @if(session('success'))
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
          </div>
        @endif
        
        @if(session('error'))
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
          </div>
        @endif

        @yield('content')
      </div>

      <!-- Footer -->
      <footer class="main-footer">
        <p>© {{ date('Y') }} {{ __('Ameen Gym') }} — {{ __('Admin Panel') }} v1.0</p>
      </footer>
    </main>
  </div>

  <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>