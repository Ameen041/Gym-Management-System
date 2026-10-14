<footer class="site-footer">
  <div class="wrap site-footer-row">
    <a href="{{ route('home') }}" class="brand">
      <span class="brand-mark"><i class="fas fa-dumbbell"></i></span>
      <span class="brand-name">{{ __('Ameen Gym') }}</span>
    </a>
    <nav class="site-footer-links">
      <a href="{{ route('coaches.index') }}">{{ __('Coaches') }}</a>
      <a href="{{ route('home') }}#how">{{ __('How it works') }}</a>
      <a href="{{ route('home') }}#for-coaches">{{ __('For coaches') }}</a>
      <a href="{{ route('home') }}#contact">{{ __('Contact Us') }}</a>
    </nav>
    <button type="button" class="btn btn-sm" data-install-app hidden><i class="fas fa-download"></i> {{ __('Install App') }}</button>
  </div>
  <div class="wrap site-copy">© {{ date('Y') }} {{ __('Ameen Gym') }} · {{ __('Online coaching') }}</div>
</footer>
