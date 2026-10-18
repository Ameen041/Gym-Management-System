<header class="site-header">
  <div class="wrap site-header-row">
    <a href="{{ route('home') }}" class="brand">
      <span class="brand-mark">@include('partials.logo-mark')</span>
      <span class="brand-name">{{ __('Himma') }}</span>
    </a>

    <nav class="site-nav" id="site-nav">
      <a href="{{ route('coaches.index') }}">{{ __('Coaches') }}</a>
      <a href="{{ route('home') }}#how">{{ __('How it works') }}</a>
      <a href="{{ route('home') }}#for-coaches">{{ __('For coaches') }}</a>
      <a href="{{ route('home') }}#contact">{{ __('Contact Us') }}</a>
      @include('partials.lang-switch', ['class' => 'nav-lang'])
    </nav>

    <div class="site-actions">
      <button type="button" class="icon-btn" data-theme-toggle title="{{ __('Day / night') }}" aria-label="{{ __('Day / night') }}">
        <i class="fas fa-moon theme-light-icon"></i><i class="fas fa-sun theme-dark-icon"></i>
      </button>
      @include('partials.lang-switch', ['class' => 'icon-btn lang-btn'])
      @auth
        <a href="{{ route(\App\Http\Middleware\Role::homeRoute(auth()->user()->role)) }}" class="btn btn-primary btn-sm">{{ __('My dashboard') }}</a>
      @else
        <button type="button" class="btn btn-sm login-btn">{{ __('Login') }}</button>
        <button type="button" class="btn btn-sm btn-primary signup-btn">{{ __('Sign Up') }}</button>
      @endauth
      <button type="button" class="icon-btn menu-btn" data-menu aria-controls="site-nav" aria-expanded="false" aria-label="{{ __('Menu') }}"><i class="fas fa-bars"></i></button>
    </div>
  </div>
</header>
