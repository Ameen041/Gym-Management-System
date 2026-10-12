<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', __('Admin')) | {{ __('Ameen Gym') }}</title>
  @include('partials.head')
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
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
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard')?'active':'' }}">
          <i class="fas fa-chart-line"></i>
          <span>{{ __('Dashboard') }}</span>
        </a>
        
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*')?'active':'' }}">
          <i class="fas fa-users"></i>
          <span>{{ __('Users') }}</span>
        </a>
        
        <a href="{{ route('admin.requests.index') }}" class="{{ request()->routeIs('admin.requests.*')?'active':'' }}">
          <i class="fas fa-envelope"></i>
          <span>{{ __('Requests') }}</span>
        </a>
        
        <a href="{{ route('admin.templates.index') }}" class="{{ request()->routeIs('admin.templates.*')?'active':'' }}">
          <i class="fas fa-file-alt"></i>
          <span>{{ __('Templates') }}</span>
        </a>
        
        <a href="{{ route('admin.exercises.index') }}" class="{{ request()->routeIs('admin.exercises.*')?'active':'' }}">
          <i class="fas fa-dumbbell"></i>
          <span>{{ __('Exercises') }}</span>
        </a>
        
        @php($unreadMessages = \App\Models\ContactMessage::where('is_read', false)->count())
        <a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages.*')?'active':'' }}">
          <i class="fas fa-inbox"></i>
          <span>{{ __('Messages') }}</span>
          @if($unreadMessages)
            <span class="badge badge-danger" style="margin-inline-start:auto;">{{ $unreadMessages }}</span>
          @endif
        </a>
        
        <a href="{{ route('admin.payments.dashboard') }}" class="{{ request()->routeIs('admin.payments.*')?'active':'' }}">
          <i class="fas fa-credit-card"></i>
          <span>{{ __('Payments') }}</span>
        </a>
        
        <a href="{{ route('admin.subscriptions.expired') }}" class="{{ request()->routeIs('admin.subscriptions.*')?'active':'' }}">
          <i class="fas fa-clock"></i>
          <span>{{ __('Expired') }}</span>
        </a>
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