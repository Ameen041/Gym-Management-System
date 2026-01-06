<header class="header">
    <div class="container">
        <nav class="navbar">
        <div class="logo">
        <i class="fas fa-dumbbell"></i>
    <span class="logo-text">Ameen Gym</span>
    </div>   
            <ul class="nav-links">
                <li class="{{ request()->routeIs('home') ? 'active' : '' }}">
                    <a href="{{ route('home') }}">Home</a>
                </li>
                <li class="{{ request()->routeIs('trainers') ? 'active' : '' }}">
                    <a href="{{ route('trainers') }}">Trainers</a>
                </li> 
                <li><a href="#programs">Programs</a></li>
                <li><a href="#nutrition">Nutrition</a></li>
                <li><a href="#pricing">Packages</a></li>
                <li><a href="#contact">Contact Us</a></li>
            </ul>
            
            <div class="auth-buttons">
                <button class="btn login-btn">Login</button>
                <button class="btn btn-primary signup-btn">Sign Up</button>
            </div>
            
            <button class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </div>
</header>