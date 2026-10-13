<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="logo">
                    <span class="logo-icon">🏋️</span>
                    <h3 class="logo-text">{{ __('Smart Fitness Club') }}</h3>
                </div>
                <p class="footer-about">
                    {{ __('We provide comprehensive fitness and nutrition solutions to help you achieve your goals.') }}
                </p>
            </div>
            
            <div class="footer-col">
                <h4 class="footer-title">{{ __('Quick Links') }}</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
                    <li><a href="{{ route('coaches.index') }}">{{ __('Coaches') }}</a></li>
                    <li><a href="{{ route('home') }}#how">{{ __('How it works') }}</a></li>
                    <li><a href="{{ route('home') }}#for-coaches">{{ __('For coaches') }}</a></li>
                    <li><a href="{{ route('home') }}#contact">{{ __('Contact Us') }}</a></li>
                </ul>
            </div>
            
            <div class="footer-col">
                <h4 class="footer-title">{{ __('Working Hours') }}</h4>
                <ul class="working-hours">
                    <li>{{ __('Saturday - Thursday: 6 AM - 12 PM') }}</li>
                    <li>{{ __('Friday: 4 PM - 12 AM') }}</li>
                </ul>
            </div>
            
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2023 {{ __('Smart Fitness Club. All Rights Reserved.') }}</p>
            <div class="legal-links">
                <a href="#">{{ __('Terms & Conditions') }}</a>
                <a href="#">{{ __('Privacy Policy') }}</a>
            </div>
        </div>
    </div>
</footer>