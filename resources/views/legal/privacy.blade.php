@extends('layouts.app')

@section('title', __('Privacy Policy') . ' | ' . __('Himma'))

@section('content')
<div class="wrap">
  <article class="legal">
    <h1>{{ __('Privacy Policy') }}</h1>
    <p class="legal-date">{{ __('Last updated: :date', ['date' => \Carbon\Carbon::parse(\App\Http\Controllers\LegalController::UPDATED)->translatedFormat('j F Y')]) }}</p>
    @include(app()->isLocale('ar') ? 'legal.privacy-ar' : 'legal.privacy-en', [
        'minAge' => \App\Http\Controllers\SubscriptionController::MIN_AGE,
    ])
    <p class="legal-other"><a href="{{ route('terms') }}"><i class="fas fa-file-contract"></i> {{ __('Terms & Conditions') }}</a></p>
  </article>
</div>
@endsection
