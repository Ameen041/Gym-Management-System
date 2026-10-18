{{-- Phone notifications (js/push.js): the site's public key and where to save a device --}}
@auth
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="push-key" content="{{ \App\Support\WebPush::publicKey() }}">
  <meta name="push-save" content="{{ route('push.store') }}">
  <meta name="push-user" content="{{ auth()->id() }}">
@endauth
