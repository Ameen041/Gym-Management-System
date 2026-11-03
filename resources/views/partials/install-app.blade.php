{{-- "Install the app": a bar at the bottom of phones and the how-to window (js/app-install.js) --}}
<div class="install-bar" data-install-bar hidden>
  <img src="{{ asset('icons/icon-192.png') }}" alt="" class="install-bar-icon" width="40" height="40">
  <div class="install-bar-text">
    <strong>{{ __('Install Himma on your phone') }}</strong>
    <span>{{ __('It opens like an app, and notifications reach you.') }}</span>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-install-app>{{ __('Install') }}</button>
  <button type="button" class="install-bar-close" data-install-dismiss aria-label="{{ __('Not now') }}"><i class="fas fa-xmark"></i></button>
</div>

<dialog class="install-help" data-install-help aria-labelledby="install-help-title">
  <form method="dialog">
    <h2 id="install-help-title"><img src="{{ asset('icons/icon-192.png') }}" alt="" width="36" height="36"> {{ __('Install Himma') }}</h2>
    {{-- shown as soon as the browser offers its own install (it can take half a minute on the site) --}}
    <div class="install-ready" data-install-ready hidden>
      <p>{{ __('Your browser is ready: install Himma with one tap.') }}</p>
      <button type="button" class="btn btn-primary btn-block" data-install-now><i class="fas fa-download"></i> {{ __('Install now') }}</button>
    </div>
    <ol data-platform="inapp" hidden>
      <li>{{ __('This page is open inside another app (Instagram, Facebook…), which cannot install apps.') }}</li>
      <li>{!! __('Tap its menu :icon and choose "Open in browser".', ['icon' => '<i class="fas fa-ellipsis"></i>']) !!}</li>
      <li>{{ __('In the browser, tap "Install App" again.') }}</li>
    </ol>
    <ol data-platform="android">
      <li>{!! __('Tap the menu :icon at the top of Chrome (in Samsung Internet, :icon2 at the bottom).', ['icon' => '<i class="fas fa-ellipsis-vertical"></i>', 'icon2' => '<i class="fas fa-bars"></i>']) !!}</li>
      <li>{{ __('Choose "Install app" or "Add to Home screen", then confirm.') }}</li>
    </ol>
    <ol data-platform="ios" hidden>
      <li>{!! __('Open the site in Safari and tap Share :icon at the bottom.', ['icon' => '<i class="fas fa-arrow-up-from-bracket"></i>']) !!}</li>
      <li>{{ __('Choose "Add to Home Screen", then "Add".') }}</li>
    </ol>
    <ol data-platform="desktop" hidden>
      <li>{{ __('Open the site in Chrome or Edge.') }}</li>
      <li>{{ __('Click the install icon at the end of the address bar (or the menu), then "Install".') }}</li>
    </ol>
    <p class="muted small">{{ __('Then Himma opens from its icon like any app, and its notifications reach you.') }}</p>
    <button class="btn btn-block">{{ __('Got it') }}</button>
  </form>
</dialog>
