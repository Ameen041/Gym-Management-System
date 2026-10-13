<header class="header">
    <div class="container">
        <nav class="navbar">
        <div class="logo">
        <i class="fas fa-dumbbell"></i>
    <span class="logo-text">{{ __('Ameen Gym') }}</span>
    </div>   
            <ul class="nav-links">
                <li class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">{{ __('Home') }}</a>
                </li>
                <li><a href="{{ route('coaches.index') }}">{{ __('Coaches') }}</a></li>
                <li><a href="{{ route('home') }}#how">{{ __('How it works') }}</a></li>
                <li><a href="{{ route('home') }}#for-coaches">{{ __('For coaches') }}</a></li>
                <li><a href="{{ route('home') }}#contact">{{ __('Contact Us') }}</a></li>
            </ul>
            
            <div class="auth-buttons">
                @include('partials.lang-switch', ['class' => 'btn btn-outline'])
                <button type="button" class="btn" data-install-app hidden><i class="fas fa-download"></i> {{ __('Install App') }}</button>
                @auth
                    <a href="{{ route(\App\Http\Middleware\Role::homeRoute(auth()->user()->role)) }}" class="btn btn-primary">{{ __('My dashboard') }}</a>
                @else
                    <button class="btn login-btn">{{ __('Login') }}</button>
                    <button class="btn btn-primary signup-btn">{{ __('Sign Up') }}</button>
                @endauth
            </div>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </div>
</header>