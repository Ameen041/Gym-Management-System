@extends('layouts.app')

@section('title', __('Himma') . ' | ' . __('Online coaching'))

@section('content')
{{-- Hero --}}
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">{{ __('Online coaching in Syria') }}</span>
      <h1>{{ __('Your personal coach, online.') }}</h1>
      <p class="hero-sub">{{ __('A workout and nutrition plan made for you, a video for every exercise, and a chat with your coach.') }}</p>
      <div class="hero-cta">
        <a href="{{ route('coaches.index') }}" class="btn btn-primary btn-lg"><i class="fas fa-magnifying-glass"></i> {{ __('Find your coach') }}</a>
        <a href="#for-coaches" class="btn btn-lg">{{ __('I am a coach') }}</a>
      </div>
      <div class="hero-stats">
        <div><b>{{ $coachesCount }}</b><span>{{ __('Coaches') }}</span></div>
        <div><b>{{ $traineesCount }}</b><span>{{ __('Trainees') }}</span></div>
        <div><b>{{ $exercisesCount }}</b><span>{{ __('Exercises') }}</span></div>
      </div>
    </div>

    {{-- A glimpse of the app: today's workout + day calories --}}
    <div class="hero-visual" aria-hidden="true">
      <div class="mock mock-plan">
        <div class="mock-head"><b>{{ __('Today') }}</b><span>{{ __('Chest & triceps') }}</span></div>
        @foreach([[__('Barbell Bench Press'), '4 × 8'], [__('Incline Dumbbell Press'), '3 × 10'], [__('Cable Chest Fly'), '3 × 12'], [__('Triceps Rope Pushdown'), '3 × 12']] as $i => [$name, $sr])
          <div class="mock-row"><span class="mock-n">{{ $i + 1 }}</span><span class="grow">{{ $name }}</span><b>{{ $sr }}</b><span class="mock-play"><i class="fas fa-play"></i></span></div>
        @endforeach
      </div>
      <div class="mock mock-kcal">
        <span class="muted small">{{ __('Day total') }}</span>
        <div class="kcal-big">2,150 <small>{{ __('kcal') }}</small></div>
        <div class="macros">
          <div class="macro"><span>{{ __('Protein') }}</span><span class="track"><span class="fill" style="width:31%"></span></span></div>
          <div class="macro carbs"><span>{{ __('Carbs') }}</span><span class="track"><span class="fill" style="width:42%"></span></span></div>
          <div class="macro fat"><span>{{ __('Fat') }}</span><span class="track"><span class="fill" style="width:27%"></span></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- How it works --}}
<section class="band" id="how">
  <div class="wrap">
    <h2 class="band-title">{{ __('How it works') }}</h2>
    <ol class="steps">
      @foreach([
          ['fa-user-check', __('Choose your coach'), __('By specialty, city and price.')],
          ['fa-clipboard-list', __('Tell us about you'), __('Your goal, level and training days.')],
          ['fa-wallet', __('Pay with Sham Cash'), __('Upload the receipt, we confirm it.')],
          ['fa-dumbbell', __('Get your plan'), __('Within :days days, with follow-up in the chat.', ['days' => \App\Models\Subscription::PLAN_DUE_DAYS])],
      ] as [$icon, $title, $text])
        <li class="step">
          <span class="step-icon"><i class="fas {{ $icon }}"></i></span>
          <h3>{{ $title }}</h3>
          <p>{{ $text }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

{{-- Coaches --}}
<section class="band band-alt" id="trainers">
  <div class="wrap">
    <div class="band-head">
      <h2 class="band-title">{{ __('Our coaches') }}</h2>
      <a href="{{ route('coaches.index') }}" class="btn btn-sm">{{ __('See all coaches') }} <i class="fas fa-arrow-left-long flip-ltr"></i></a>
    </div>
    @if($featuredTrainers->isNotEmpty())
      <div class="grid-3">
        @foreach($featuredTrainers as $coach)
          @include('partials.coach-card', ['coach' => $coach])
        @endforeach
      </div>
    @else
      <p class="muted">{{ __('Trainers will appear here soon.') }}</p>
    @endif
  </div>
</section>

@if($latestReviews->isNotEmpty())
  <section class="band">
    <div class="wrap">
      <h2 class="band-title">{{ __('What trainees say') }}</h2>
      <div class="grid-3">
        @foreach($latestReviews as $review)
          <figure class="quote">
            <div class="stars">{{ str_repeat('★', $review->rating) }}</div>
            <blockquote>{{ $review->comment }}</blockquote>
            <figcaption class="muted small">{{ \Illuminate\Support\Str::of($review->trainee->name ?? '')->explode(' ')->first() }} · {{ __('Coach') }} {{ $review->coach->name ?? '' }}</figcaption>
          </figure>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- For coaches --}}
<section class="band" id="for-coaches">
  <div class="wrap">
    <div class="coach-band">
      <div>
        <span class="eyebrow">{{ __('For coaches') }}</span>
        <h2>{{ __('Train clients online and build their plans in minutes.') }}</h2>
        <ul class="checks">
          <li><i class="fas fa-check"></i> {{ __('Ready exercise and food library: add with one tap.') }}</li>
          <li><i class="fas fa-check"></i> {{ __('Your own packages and prices.') }}</li>
          <li><i class="fas fa-check"></i> {{ __('We collect payments and send your earnings on Sham Cash.') }}</li>
        </ul>
      </div>
      <div class="coach-band-cta">
        @guest
          <button type="button" class="btn btn-primary btn-lg" data-signup-coach>{{ __('Join as a coach') }}</button>
        @else
          @if(auth()->user()->role === 'trainer')
            <a href="{{ route('trainer.dashboard') }}" class="btn btn-primary btn-lg">{{ __('Go to my dashboard') }}</a>
          @endif
        @endguest
      </div>
    </div>
  </div>
</section>

{{-- Contact --}}
<section class="band band-alt" id="contact">
  <div class="wrap contact-grid">
    <div>
      <h2 class="band-title">{{ __('Contact Us') }}</h2>
      <ul class="contact-list">
        <li><i class="fas fa-phone"></i> <span dir="ltr">0945004651</span></li>
        <li><i class="fas fa-envelope"></i> <span>aminjalal@gmail.com</span></li>
        <li><i class="fas fa-location-dot"></i> <span>{{ __('Damascus Countryside, Syrian Arab Republic') }}</span></li>
      </ul>
    </div>
    <form class="panel contact-form" method="POST" action="{{ route('contact.store') }}">
      @csrf
      @if(session('contact_success'))
        <div class="alert alert-success"><i class="fas fa-circle-check"></i><div>{{ session('contact_success') }}</div></div>
      @endif
      @if($errors->contact->any())
        <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><div>{{ $errors->contact->first() }}</div></div>
      @endif
      <div class="form-row">
        <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Full Name') }}" aria-label="{{ __('Full Name') }}" required>
        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone Number') }}" aria-label="{{ __('Phone Number') }}">
      </div>
      <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}" aria-label="{{ __('Email') }}" required>
      <textarea name="message" rows="4" placeholder="{{ __('Your Message') }}" aria-label="{{ __('Your Message') }}" required>{{ old('message') }}</textarea>
      <button type="submit" class="btn btn-primary">{{ __('Send Message') }}</button>
    </form>
  </div>
</section>
@endsection
