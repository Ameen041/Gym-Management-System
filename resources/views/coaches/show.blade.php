@extends('layouts.panel')
@section('title', $coach->name)

@section('content')
@php use App\Support\Money; @endphp

@unless($isListed)
  <div class="alert alert-warning">
    <i class="fas fa-eye"></i>
    <div>{{ __('Preview: this profile is not visible to trainees yet.') }}</div>
  </div>
@endunless

<div class="grid-2" style="align-items:start">
  <div class="stack">
    <section class="panel">
      <div class="profile-head">
        <img src="{{ $coach->photo_url }}" alt="{{ $coach->name }}" class="avatar-lg">
        <div class="stack" style="flex:1;min-width:200px">
          <h1 style="font-size:1.4rem">{{ $coach->name }}
            @if($profile->is_featured)<span class="badge badge-warn"><i class="fas fa-crown"></i> {{ __('Featured') }}</span>@endif
          </h1>
          @include('partials.stars', ['rating' => $profile->rating_avg, 'count' => $profile->rating_count])
          <div class="coach-meta">
            @if($profile->city_label)<span><i class="fas fa-map-marker-alt"></i> {{ $profile->city_label }}</span>@endif
            @if($profile->experience_years)<span><i class="fas fa-medal"></i> {{ __(':years years experience', ['years' => $profile->experience_years]) }}</span>@endif
            <span><i class="fas fa-users"></i> {{ __(':count clients', ['count' => $clientsCount]) }}</span>
          </div>
          <div class="tags">
            @foreach($profile->specialty_labels as $label)
              <span class="badge">{{ $label }}</span>
            @endforeach
          </div>
        </div>
      </div>
    </section>

    @if($profile->bio)
      <section class="panel">
        <div class="panel-head"><h2><i class="fas fa-user"></i> {{ __('About the coach') }}</h2></div>
        <p style="white-space:pre-line">{{ $profile->bio }}</p>
      </section>
    @endif

    @if(!empty($profile->certificates))
      <section class="panel">
        <div class="panel-head"><h2><i class="fas fa-certificate"></i> {{ __('Certificates') }}</h2></div>
        <div class="thumbs">
          @foreach($profile->certificates as $path)
            <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener"><img src="{{ asset('storage/' . $path) }}" alt="{{ __('Certificate') }}" loading="lazy"></a>
          @endforeach
        </div>
      </section>
    @endif

    <section class="panel">
      <div class="panel-head"><h2><i class="fas fa-star"></i> {{ __('Reviews') }}</h2></div>
      @forelse($reviews as $review)
        <div class="review">
          <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <strong>{{ \Illuminate\Support\Str::of($review->trainee->name ?? __('Trainee'))->explode(' ')->first() }}</strong>
            @include('partials.stars', ['rating' => $review->rating, 'count' => null])
          </div>
          @if($review->comment)<p style="margin-top:6px">{{ $review->comment }}</p>@endif
          <small class="muted">{{ $review->created_at->translatedFormat('j M Y') }}</small>
        </div>
      @empty
        <p class="muted">{{ __('No reviews yet.') }}</p>
      @endforelse
    </section>
  </div>

  <div class="stack">
    <section class="panel">
      <div class="panel-head"><h2><i class="fas fa-tags"></i> {{ __('Packages') }}</h2></div>
      <div class="stack">
        @forelse($packages as $package)
          <div class="package">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;flex-wrap:wrap">
              <h4>{{ $package->title }}</h4>
              <span class="price">{{ Money::format($package->price) }} <small>/ {{ __(':days days', ['days' => $package->duration_days]) }}</small></span>
            </div>
            <div class="muted small"><i class="fas fa-check-circle" style="color:var(--success)"></i> {{ $package->includes_label }}</div>
            @if($package->description)<p class="small" style="white-space:pre-line">{{ $package->description }}</p>@endif

            @auth
              @if(auth()->user()->role === 'trainee' && $isListed)
                <a href="{{ route('subscriptions.create', [$coach, $package]) }}" class="btn btn-primary btn-block">
                  <i class="fas fa-bolt"></i> {{ __('Subscribe') }}
                </a>
              @endif
            @else
              <a href="{{ route('login.form') }}" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> {{ __('Log in as a trainee to subscribe') }}
              </a>
            @endauth
          </div>
        @empty
          <p class="muted">{{ __('This coach has no packages yet.') }}</p>
        @endforelse
      </div>
    </section>

    <section class="panel">
      <div class="panel-head"><h2><i class="fas fa-route"></i> {{ __('How it works') }}</h2></div>
      <ol class="steps-list">
        <li>{{ __('Choose a package and answer a few questions about your goal.') }}</li>
        <li>{{ __('Pay with Sham Cash and upload the receipt.') }}</li>
        <li>{{ __('After we confirm the payment, your coach sends your plan within :days days.', ['days' => \App\Models\Subscription::PLAN_DUE_DAYS]) }}</li>
        <li>{{ __('Follow up with your coach directly on WhatsApp.') }}</li>
      </ol>
    </section>
  </div>
</div>
@endsection
