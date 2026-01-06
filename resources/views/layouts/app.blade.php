<!DOCTYPE html>
<html lang="ar" dir="ltr">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/layout.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<head>
<title> @yield('title')

</title>

@yield('custom_css')
</head>
<body>
@include('partials.navbar')

<main>
    @yield('content')
</main>

@include('partials.auth-modal')

@include('partials.footer')

@yield('custom_js')

</body>
</html>    
