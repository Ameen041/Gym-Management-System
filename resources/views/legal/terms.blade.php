@extends('layouts.app')

@section('title', __('Terms & Conditions') . ' | ' . __('Himma'))

@section('content')
<div class="wrap">
  <article class="legal">
    <h1>{{ __('Terms & Conditions') }}</h1>
    <p class="legal-date">{{ __('Last updated: :date', ['date' => \Carbon\Carbon::parse(\App\Http\Controllers\LegalController::UPDATED)->translatedFormat('j F Y')]) }}</p>
    @include(app()->isLocale('ar') ? 'legal.terms-ar' : 'legal.terms-en', [
        'planDays' => \App\Models\Subscription::PLAN_DUE_DAYS,
        'chatDays' => \App\Models\Conversation::OPEN_DAYS_AFTER_END,
    ])
    <p class="legal-other"><a href="{{ route('privacy') }}"><i class="fas fa-shield-halved"></i> {{ __('Privacy Policy') }}</a></p>
  </article>
</div>
@endsection
