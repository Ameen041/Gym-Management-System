@include('partials.pwa')
<link rel="stylesheet" href="{{ asset('vendor/fonts/fonts.css') }}">
@if(app()->isLocale('ar'))
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/rtl.css') }}">
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
