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
    {{-- Safari on iOS 26 keeps Share in the ⋯ menu; older iPhones have it in the bar at the bottom --}}
    <ol data-platform="ios" hidden>
      <li>{!! __('Tap :more next to the address bar, then Share :icon.', ['more' => '<i class="fas fa-ellipsis"></i>', 'icon' => '<i class="fas fa-arrow-up-from-bracket"></i>']) !!}
        <span class="muted small" style="display:block">{{ __('On older iPhones, Share is right in the bar at the bottom.') }}</span></li>
      <li>{{ __('Scroll down and choose "Add to Home Screen". Keep "Open as Web App" on, then tap "Add".') }}</li>
      <li>{{ __('Open Himma from its new icon and log in once: the app keeps its own login.') }}</li>
    </ol>
    {{-- Chrome, Edge or Firefox on an iPhone --}}
    <ol data-platform="ios-browser" hidden>
      <li>{!! __('Tap Share :icon (in Chrome it is in the address bar, in other browsers in the menu).', ['icon' => '<i class="fas fa-arrow-up-from-bracket"></i>']) !!}</li>
      <li>{{ __('Choose "Add to Home Screen", then "Add".') }}</li>
      <li>{{ __('Not in the list? Open the site in Safari and tap "Install App" there.') }}</li>
    </ol>
    <ol data-platform="desktop" hidden>
      <li>{{ __('Open the site in Chrome or Edge.') }}</li>
      <li>{{ __('Click the install icon at the end of the address bar (or the menu), then "Install".') }}</li>
    </ol>
    <p class="muted small">{{ __('Then Himma opens from its icon like any app, and its notifications reach you.') }}</p>
    <button class="btn btn-block">{{ __('Got it') }}</button>
  </form>
</dialog>
