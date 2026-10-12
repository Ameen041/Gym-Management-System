@php
  $dashboardRoute = match (auth()->user()->role) {
      'admin'   => route('admin.dashboard'),
      'trainer' => route('trainer.dashboard'),
      default   => route('trainee.dashboard'),
  };
@endphp
<header class="topbar">
  <div class="brand">
    <div class="brand-icon"><i class="fas fa-dumbbell"></i></div>
    <div class="brand-text">
      <h1>{{ $heading }}</h1>
      <p>{{ $subheading }}</p>
    </div>
  </div>
  <div class="topbar-right">
    <button type="button" class="btn btn-primary" data-install-app hidden>
      <i class="fas fa-download"></i><span>Install App</span>
    </button>
    @if(isset($back))
      <a href="{{ $back }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i><span>Exercises</span></a>
    @endif
    <a href="{{ $dashboardRoute }}" class="btn btn-ghost"><i class="fas fa-home"></i><span>Dashboard</span></a>
  </div>
</header>
