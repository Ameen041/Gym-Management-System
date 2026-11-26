{{-- Installable app (PWA) meta tags --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#090b0e">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ __('Himma') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
<script src="{{ \App\Support\Asset::url('js/app-install.js') }}" defer></script>
@if(in_array(auth()->user()?->role, ['trainee', 'trainer'], true))
  {{-- the phone keeps this person's pages, plans and exercise pictures for when there is no internet (js/offline.js) --}}
  <meta name="offline-pack" content="{{ route('offline.pack', [], false) }}">
@endif
<script src="{{ \App\Support\Asset::url('js/offline.js') }}" defer></script>
<style>[data-install-app][hidden]{display:none !important}</style>
