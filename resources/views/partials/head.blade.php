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
      'chat_failed'      => __('The message was not sent. Check your connection and try again.'),
      'offline_now'      => __('No internet. You can still open the pages kept on this phone.'),
      'offline_copy'     => __('No internet: this is the copy kept on this phone (:time).'),
      'offline_back'     => __('The internet is back.'),
      'offline_reload'   => __('Refresh the page'),
      'offline_not_sent' => __('No internet: nothing was sent. What you wrote is still here; send it when the internet is back.'),
  ];
@endphp
<script>window.I18N = @json($jsI18n);</script>
