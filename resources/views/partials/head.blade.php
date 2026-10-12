@include('partials.pwa')
@if(app()->isLocale('ar'))
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
@endif
@php
  $jsI18n = [
      'confirm_delete'   => __('Are you sure you want to delete this?'),
      'field_required'   => __('This field is required'),
      'min_exercise_row' => __('This day must contain at least one exercise row.'),
      'min_meal_row'     => __('This day must contain at least one meal row.'),
      'install_ios'      => __('To install Ameen Gym: tap the Share button, then "Add to Home Screen".'),
  ];
@endphp
<script>window.I18N = @json($jsI18n);</script>
