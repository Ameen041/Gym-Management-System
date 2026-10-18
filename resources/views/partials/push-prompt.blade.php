{{-- Asks to turn on phone notifications; shown by js/push.js only where the browser can and hasn't been asked yet --}}
<div class="push-card" data-push-card hidden>
  <i class="fas fa-bell"></i>
  <div class="grow">
    <strong>{{ __('Get notified on your phone') }}</strong>
    <span class="muted small">{{ __('Know right away when a message, a plan or a reply arrives.') }}</span>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-push-on>{{ __('Turn on') }}</button>
  <button type="button" class="icon-btn" data-push-later title="{{ __('Later') }}" aria-label="{{ __('Later') }}"><i class="fas fa-xmark"></i></button>
</div>
