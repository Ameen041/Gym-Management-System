@extends('layouts.app')

@section('title', __('Himma') . ' | ' . __('Online coaching'))

@section('content')
{{-- Hero --}}
<section class="hero">
  <div class="hero-bg" aria-hidden="true"><span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span></div>
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <span class="eyebrow hero-pill" style="--i:0"><span class="live-dot"></span> {{ __('Online coaching in Syria') }}</span>
      <h1 style="--i:1">{{ __('Your personal coach, online.') }}</h1>
      <p class="hero-rotator" style="--i:2" data-rotator aria-live="off">
        @foreach([__('A workout plan made for you'), __('A nutrition plan with calories and macros'), __('Weekly follow-up of your weight'), __('A chat with your coach, numbers stay private')] as $line)
          <span class="{{ $loop->first ? 'on' : '' }}"><i class="fas fa-circle-check"></i> {{ $line }}</span>
        @endforeach
      </p>
      <p class="hero-sub" style="--i:3">{{ __('A workout and nutrition plan made for you, a video for every exercise, and a chat with your coach.') }}</p>
      <div class="hero-cta" style="--i:4">
        <a href="{{ route('coaches.index') }}" class="btn btn-primary btn-lg btn-shine"><i class="fas fa-magnifying-glass"></i> {{ __('Find your coach') }}</a>
        <a href="#for-coaches" class="btn btn-lg">{{ __('I am a coach') }}</a>
      </div>
      <div class="hero-stats" style="--i:5">
        <div><b data-count="{{ $exercisesCount }}">{{ $exercisesCount }}</b><span>{{ __('Exercises with video') }}</span></div>
        @if($coachesCount >= 3)
          <div><b data-count="{{ $coachesCount }}">{{ $coachesCount }}</b><span>{{ __('Coaches') }}</span></div>
        @endif
        @if($traineesCount >= 50)
          <div><b data-count="{{ $traineesCount }}">{{ $traineesCount }}</b><span>{{ __('Trainees') }}</span></div>
        @endif
        <div><b data-count="{{ \App\Models\Subscription::PLAN_DUE_DAYS }}">{{ \App\Models\Subscription::PLAN_DUE_DAYS }}</b><span>{{ __('days at most for your plan') }}</span></div>
      </div>
    </div>

    {{-- A glimpse of the app: today's workout, the day's calories and the chat --}}
    <div class="hero-visual" aria-hidden="true">
      <div class="mock mock-chat float-b">
        <div class="mock-msg them">{{ __('Coach, I finished today\'s workout 💪') }}</div>
        <div class="mock-msg me typing"><span></span><span></span><span></span></div>
        <div class="mock-msg me reply">{{ __('Great! Add 2.5 kg next week.') }}</div>
      </div>
      <div class="mock mock-plan float-a">
        <div class="mock-head"><b>{{ __('Today') }}</b><span>{{ __('Chest & triceps') }}</span></div>
        @foreach([[__('Barbell Bench Press'), '4 × 8'], [__('Incline Dumbbell Press'), '3 × 10'], [__('Cable Chest Fly'), '3 × 12'], [__('Triceps Rope Pushdown'), '3 × 12']] as $i => [$name, $sr])
          <div class="mock-row" style="--i:{{ $i }}"><span class="mock-n">{{ $i === 0 ? '✓' : $i + 1 }}</span><span class="grow">{{ $name }}</span><b>{{ $sr }}</b><span class="mock-play"><i class="fas fa-play"></i></span></div>
        @endforeach
      </div>
      <div class="mock mock-kcal float-c">
        <span class="muted small">{{ __('Day total') }}</span>
        <div class="kcal-big"><span data-count="2150">2,150</span> <small>{{ __('kcal') }}</small></div>
        <div class="macros">
          <div class="macro"><span>{{ __('Protein') }}</span><span class="track"><span class="fill" style="width:31%"></span></span></div>
          <div class="macro carbs"><span>{{ __('Carbs') }}</span><span class="track"><span class="fill" style="width:42%"></span></span></div>
          <div class="macro fat"><span>{{ __('Fat') }}</span><span class="track"><span class="fill" style="width:27%"></span></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- What you get, moving --}}
<div class="marquee" aria-hidden="true">
  <div class="marquee-track">
    @for($copy = 0; $copy < 2; $copy++)
      @foreach([['fa-dumbbell', __('A workout plan made for you')], ['fa-apple-whole', __('Calories and macros for every day')], ['fa-circle-play', __('A video for every exercise')], ['fa-comments', __('A chat with your coach')], ['fa-chart-line', __('Weekly check-ins and a weight chart')], ['fa-wallet', __('Pay with Sham Cash')], ['fa-bell', __('Notifications on your phone')]] as [$icon, $text])
        <span class="marquee-item"><i class="fas {{ $icon }}"></i> {{ $text }}</span>
      @endforeach
    @endfor
  </div>
</div>

{{-- How it works --}}
<section class="band" id="how">
  <div class="wrap">
    <h2 class="band-title" data-reveal>{{ __('How it works') }}</h2>
    <ol class="steps">
      @foreach([
          ['fa-user-check', __('Choose your coach'), __('By specialty, city and price.')],
          ['fa-clipboard-list', __('Tell us about you'), __('Your goal, level and training days.')],
          ['fa-wallet', __('Pay with Sham Cash'), __('Upload the receipt, we confirm it.')],
          ['fa-dumbbell', __('Get your plan'), __('Within :days days, with follow-up in the chat.', ['days' => \App\Models\Subscription::PLAN_DUE_DAYS])],
      ] as $n => [$icon, $title, $text])
        <li class="step" data-reveal style="--i:{{ $n }}">
          <span class="step-icon"><i class="fas {{ $icon }}"></i></span>
          <h3>{{ $title }}</h3>
          <p>{{ $text }}</p>
        </li>
      @endforeach
    </ol>
  </div>
</section>

{{-- Everything in one place: four small live pictures of the app --}}
<section class="band band-alt" id="features">
  <div class="wrap">
    <h2 class="band-title" data-reveal>{{ __('Everything you need to reach your goal') }}</h2>
    <div class="bento">
      <article class="feature feature-chat" data-reveal style="--i:0">
        <div class="feature-art" aria-hidden="true">
          <div class="bubble them">{{ __('Can I swap squats for leg press?') }}</div>
          <div class="bubble me">{{ __('Yes, 4 sets of 12 this week.') }}</div>
          <div class="bubble them">{{ __('Thanks coach! 🙏') }}</div>
        </div>
        <h3><i class="fas fa-comments"></i> {{ __('Chat with your coach') }}</h3>
        <p>{{ __('Ask anything about your plan, inside Himma. Your number stays private.') }}</p>
      </article>

      <article class="feature feature-macros" data-reveal style="--i:1">
        <div class="feature-art" aria-hidden="true">
          @foreach([[__('Calories'), 96], [__('Protein'), 100], [__('Carbs'), 92], [__('Fat'), 104]] as $j => [$label, $pct])
            <div class="range-mini" style="--to:{{ $pct / 1.3 }}%;--j:{{ $j }}"><span>{{ $label }}</span><span class="rm-track"><span class="rm-zone"></span><span class="rm-fill"></span></span></div>
          @endforeach
        </div>
        <h3><i class="fas fa-bullseye"></i> {{ __('Food that hits your numbers') }}</h3>
        <p>{{ __('Your coach sets your daily calories and macros, then fills every day until it is on target.') }}</p>
      </article>

      <article class="feature feature-progress" data-reveal style="--i:2">
        <div class="feature-art" aria-hidden="true">
          <svg viewBox="0 0 220 90" class="draw-chart" preserveAspectRatio="none">
            <path class="area" d="M0 20 L40 28 L80 38 L120 44 L160 56 L200 62 L220 66 L220 90 L0 90 Z"/>
            <path class="line" d="M0 20 L40 28 L80 38 L120 44 L160 56 L200 62 L220 66" pathLength="1"/>
            @foreach([[0,20],[40,28],[80,38],[120,44],[160,56],[200,62]] as $k => [$x, $y])
              <circle cx="{{ $x }}" cy="{{ $y }}" r="3.5" style="--k:{{ $k }}"/>
            @endforeach
          </svg>
          <span class="chart-badge">−5.4 {{ __('kg') }}</span>
        </div>
        <h3><i class="fas fa-chart-line"></i> {{ __('See your progress') }}</h3>
        <p>{{ __('A weekly check-in, a weight chart and a reply from your coach every week.') }}</p>
      </article>

      <article class="feature feature-library" data-reveal style="--i:3">
        <div class="feature-art" aria-hidden="true">
          <span class="play-pulse"><i class="fas fa-play"></i></span>
          <b><span data-count="{{ $exercisesCount }}">{{ $exercisesCount }}</span>+</b>
        </div>
        <h3><i class="fas fa-circle-play"></i> {{ __('A video for every exercise') }}</h3>
        <p>{{ __('Every exercise in your plan opens its video, so you train right.') }}</p>
      </article>
    </div>
  </div>
</section>

{{-- Coaches --}}
<section class="band" id="trainers">
  <div class="wrap">
    <div class="band-head">
      <h2 class="band-title" data-reveal>{{ __('Our coaches') }}</h2>
      <a href="{{ route('coaches.index') }}" class="btn btn-sm">{{ __('See all coaches') }} <i class="fas fa-arrow-left-long flip-ltr"></i></a>
    </div>
    @if($featuredTrainers->isNotEmpty())
      <div class="grid-3">
        @foreach($featuredTrainers as $coach)
          <div data-reveal style="--i:{{ $loop->index }}">@include('partials.coach-card', ['coach' => $coach])</div>
        @endforeach
      </div>
    @else
      <p class="muted">{{ __('Trainers will appear here soon.') }}</p>
    @endif
  </div>
</section>

@if($latestReviews->isNotEmpty())
  <section class="band band-alt">
    <div class="wrap">
      <h2 class="band-title" data-reveal>{{ __('What trainees say') }}</h2>
      <div class="grid-3">
        @foreach($latestReviews as $review)
          <figure class="quote" data-reveal style="--i:{{ $loop->index }}">
            <div class="stars">{{ str_repeat('★', $review->rating) }}</div>
            <blockquote>{{ $review->comment }}</blockquote>
            <figcaption class="muted small">{{ $review->trainee && !$review->trainee->deleted_at ? \Illuminate\Support\Str::of($review->trainee->name)->explode(' ')->first() : __('Former client') }} · {{ __('Coach') }} {{ $review->coach->name ?? '' }}</figcaption>
          </figure>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- For coaches --}}
<section class="band" id="for-coaches">
  <div class="wrap">
    <div class="coach-band" data-reveal>
      <span class="coach-band-glow" aria-hidden="true"></span>
      <div>
        <span class="eyebrow">{{ __('For coaches') }}</span>
        <h2>{{ __('Train clients online and build their plans in minutes.') }}</h2>
        <ul class="checks">
          <li><i class="fas fa-check"></i> {{ __('Ready exercise and food library: add with one tap.') }}</li>
          <li><i class="fas fa-check"></i> {{ __('Your own packages and prices.') }}</li>
          <li><i class="fas fa-check"></i> {{ __('We collect payments and send your earnings on Sham Cash.') }}</li>
          <li><i class="fas fa-check"></i> {{ __('Bring your own clients with your invite link: Himma takes only :rate% on them.', ['rate' => \App\Models\Setting::inviteCommissionRate() + 0]) }}</li>
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
    <div data-reveal>
      <h2 class="band-title">{{ __('Contact Us') }}</h2>
      <ul class="contact-list">
        <li><i class="fas fa-phone"></i> <span dir="ltr">0945004651</span></li>
        <li><i class="fas fa-envelope"></i> <span>aminjalal@gmail.com</span></li>
        <li><i class="fas fa-location-dot"></i> <span>{{ __('Damascus Countryside, Syrian Arab Republic') }}</span></li>
      </ul>
    </div>
    <form class="panel contact-form" method="POST" action="{{ route('contact.store') }}" data-reveal style="--i:1">
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
