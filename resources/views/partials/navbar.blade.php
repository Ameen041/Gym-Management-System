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
                <li><a href="#trainers">{{ __('Trainers') }}</a></li>
                <li><a href="#programs">{{ __('Programs') }}</a></li>
                <li><a href="#nutrition">{{ __('Nutrition') }}</a></li>
                <li><a href="#pricing">{{ __('Packages') }}</a></li>
                <li><a href="#contact">{{ __('Contact Us') }}</a></li>
            </ul>
            
            <div class="auth-buttons">
                @include('partials.lang-switch', ['class' => 'btn btn-outline'])
                <button type="button" class="btn" data-install-app hidden><i class="fas fa-download"></i> {{ __('Install App') }}</button>
                <button class="btn login-btn">{{ __('Login') }}</button>
                <button class="btn btn-primary signup-btn">{{ __('Sign Up') }}</button>
            </div>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </div>
</header>