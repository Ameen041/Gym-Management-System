<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>@yield('title')</title>
  @include('partials.theme-init')
  @include('partials.head')
  <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/app.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/site.css') }}">
  @yield('custom_css')
</head>
<body class="site">
@include('partials.navbar')

<main>
  @if(session('goodbye'))
    <div class="wrap site-flash"><div class="alert alert-success" role="status"><i class="fas fa-circle-check"></i><div>{{ session('goodbye') }}</div></div></div>
  @endif
  @yield('content')
</main>

@include('partials.auth-modal')
@include('partials.footer')

<script src="{{ \App\Support\Asset::url('js/app.js') }}" defer></script>
<script src="{{ \App\Support\Asset::url('js/site.js') }}" defer></script>
@yield('custom_js')
</body>
</html>
