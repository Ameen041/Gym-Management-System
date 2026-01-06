<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Admin') | Ameen Gym</title>
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
            <h1>Ameen Gym</h1>
            <p>Admin Panel</p>
          </div>
        </div>
      </div>

      <nav class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard')?'active':'' }}">
          <i class="fas fa-chart-line"></i>
          <span>Dashboard</span>
        </a>
        
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*')?'active':'' }}">
          <i class="fas fa-users"></i>
          <span>Users</span>
        </a>
        
        <a href="{{ route('admin.requests.index') }}" class="{{ request()->routeIs('admin.requests.*')?'active':'' }}">
          <i class="fas fa-envelope"></i>
          <span>Requests</span>
        </a>
        
        <a href="{{ route('admin.templates.index') }}" class="{{ request()->routeIs('admin.templates.*')?'active':'' }}">
          <i class="fas fa-file-alt"></i>
          <span>Templates</span>
        </a>
        
        <a href="{{ route('admin.payments.dashboard') }}" class="{{ request()->routeIs('admin.payments.*')?'active':'' }}">
          <i class="fas fa-credit-card"></i>
          <span>Payments</span>
        </a>
        
        <a href="{{ route('admin.subscriptions.expired') }}" class="{{ request()->routeIs('admin.subscriptions.*')?'active':'' }}">
          <i class="fas fa-clock"></i>
          <span>Expired</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <form method="POST" action="{{ url('/logout') }}">
          @csrf
          <button type="submit" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
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
          <h2>@yield('page-title', 'Dashboard')</h2>
        </div>
        <div class="header-right">
          <div class="admin-info">
            <i class="fas fa-user-circle"></i>
            <span>{{ auth()->user()->name ?? 'Admin' }}</span>
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
        <p>© {{ date('Y') }} Ameen Gym — Admin Panel v1.0</p>
      </footer>
    </main>
  </div>

  <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>