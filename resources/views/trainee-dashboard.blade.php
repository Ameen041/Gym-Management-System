@extends('layouts.panel')
@section('title', __('Trainee Dashboard'))

@section('content')
@php use App\Support\Money; @endphp

<div class="page-title">
  <div>
    <span class="eyebrow">{{ now()->translatedFormat('l j F') }}</span>
    <h1>{{ __('Hi :name', ['name' => \Illuminate\Support\Str::before($user->name, ' ')]) }}</h1>
  </div>
</div>

{{-- Running subscriptions --}}
@forelse($running as $sub)
  <section class="panel">
    <div class="sub-head">
      <img src="{{ $sub->coach->photo_url }}" alt="" class="avatar-sm">
      <div class="grow">
        <h2>{{ $sub->coach->name }}</h2>
        <span class="muted small">{{ $sub->package_title }}</span>
      </div>
      <span class="badge badge-ok">{{ __('Until :date', ['date' => $sub->ends_at->translatedFormat('j M')]) }}</span>
    </div>

    <div class="tiles">
      @if($sub->includes_workout)
        @if($sub->workoutPlan)
          <a href="{{ route('trainee.schedule', ['subscription' => $sub->id]) }}" class="tile tile-primary">
            <i class="fas fa-dumbbell"></i><b>{{ __('Workout plan') }}</b><small>{{ __('Open') }}</small>
          </a>
        @else
          <div class="tile tile-wait"><i class="fas fa-hourglass-half"></i><b>{{ __('Workout plan') }}</b><small>{{ __('Ready by :date', ['date' => $sub->plan_due_at->translatedFormat('j M')]) }}</small></div>
        @endif
      @endif
      @if($sub->includes_nutrition)
        @if($sub->nutritionPlan)
          <a href="{{ route('trainee.diet', ['subscription' => $sub->id]) }}" class="tile tile-primary">
            <i class="fas fa-utensils"></i><b>{{ __('Nutrition plan') }}</b><small>{{ __('Open') }}</small>
          </a>
        @else
          <div class="tile tile-wait"><i class="fas fa-hourglass-half"></i><b>{{ __('Nutrition plan') }}</b><small>{{ __('Ready by :date', ['date' => $sub->plan_due_at->translatedFormat('j M')]) }}</small></div>
        @endif
      @endif
      @if($sub->coach->whatsapp_url)
        <a href="{{ $sub->coach->whatsapp_url }}" target="_blank" rel="noopener" class="tile tile-wa">
          <i class="fab fa-whatsapp"></i><b>{{ __('WhatsApp') }}</b><small>{{ __('Talk to your coach') }}</small>
        </a>
      @endif
    </div>

    @if($sub->review)
      <div class="sub-review muted small">{{ __('Your review') }} @include('partials.stars', ['rating' => $sub->review->rating, 'count' => null])</div>
    @else
      <details class="sub-rate">
        <summary><i class="fas fa-star"></i> {{ __('Rate your coach') }}</summary>
        <form method="POST" action="{{ route('subscriptions.review', $sub) }}" class="form-row" style="margin-top:10px;align-items:end">
          @csrf
          <select name="rating" required aria-label="{{ __('Rating') }}">
            @for($r = 5; $r >= 1; $r--)
              <option value="{{ $r }}">{{ str_repeat('★', $r) }}</option>
            @endfor
          </select>
          <input type="text" name="comment" maxlength="1000" placeholder="{{ __('Write a short review (optional)') }}" aria-label="{{ __('Review') }}">
          <button class="btn btn-primary">{{ __('Send review') }}</button>
        </form>
      </details>
    @endif
  </section>
@empty
  @if($pending->isEmpty())
    <section class="panel empty">
      <i class="fas fa-user-tie"></i>
      <h3>{{ __('You have no coach yet') }}</h3>
      <p>{{ __('Choose an online coach to get your workout and nutrition plan.') }}</p>
      <a href="{{ route('coaches.index') }}" class="btn btn-primary" style="margin-top:12px"><i class="fas fa-search"></i> {{ __('Find your coach') }}</a>
    </section>
  @endif
@endforelse

{{-- Waiting for payment / review --}}
@foreach($pending as $sub)
  <section class="panel">
    <div class="list-row" style="padding:0">
      <img src="{{ $sub->coach->photo_url }}" alt="" class="avatar-sm">
      <div class="grow">
        <strong>{{ $sub->coach->name }}</strong> · <span class="muted">{{ $sub->package_title }} · {{ Money::format($sub->price) }}</span>
        <div>
          @if($sub->status === 'payment_review')
            <span class="badge badge-warn"><i class="fas fa-hourglass-half"></i> {{ $sub->status_label }}</span>
          @elseif($sub->status === 'rejected')
            <span class="badge badge-danger"><i class="fas fa-times-circle"></i> {{ $sub->status_label }}</span>
            @if($sub->admin_note)<span class="muted small">{{ $sub->admin_note }}</span>@endif
          @else
            <span class="badge badge-muted"><i class="fas fa-wallet"></i> {{ $sub->status_label }}</span>
          @endif
        </div>
      </div>
      @if(in_array($sub->status, ['awaiting_payment', 'rejected']))
        <a href="{{ route('subscriptions.pay', $sub) }}" class="btn btn-primary"><i class="fas fa-wallet"></i> {{ __('Complete payment') }}</a>
      @endif
    </div>
  </section>
@endforeach

<div class="grid-2" style="margin-top:14px;align-items:start">
  {{-- Profile --}}
  <details class="panel fold" @if($errors->any()) open @endif>
    <summary class="panel-head" style="margin:0"><h2><i class="fas fa-user"></i> {{ __('Your Profile') }}</h2><i class="fas fa-chevron-down fold-icon"></i></summary>
    <div style="margin-top:14px">
    <form method="POST" action="{{ route('trainee.profile.update') }}" enctype="multipart/form-data">
      @csrf
      <div class="profile-head" style="margin-bottom:14px">
        <img src="{{ $user->photo_url }}" alt="" class="avatar-sm" style="width:64px;height:64px">
        <div class="form-group" style="flex:1;margin:0">
          <label>{{ __('Profile Photo') }}</label>
          <input type="file" name="photo" accept="image/*">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>{{ __('Name') }}</label>
          <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="form-group">
          <label>{{ __('WhatsApp number') }}</label>
          <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xxxxxxxx">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>{{ __('Age') }}</label>
          <input type="number" name="age" value="{{ old('age', $user->age) }}">
        </div>
        <div class="form-group">
          <label>{{ __('Height (cm)') }}</label>
          <input type="number" name="height" value="{{ old('height', $user->height) }}">
        </div>
        <div class="form-group">
          <label>{{ __('Weight (kg)') }}</label>
          <input type="number" name="weight" step="0.1" value="{{ old('weight', $user->weight) }}">
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('Save') }}</button>
    </form>
    </div>
  </details>

  {{-- History --}}
  <section class="panel">
    <div class="panel-head"><h2><i class="fas fa-history"></i> {{ __('Previous subscriptions') }}</h2></div>
    @forelse($history as $sub)
      <div class="list-row">
        <div class="grow">
          <strong>{{ $sub->coach->name }}</strong> · <span class="muted small">{{ $sub->package_title }}</span>
          <div class="muted small">{{ $sub->starts_at?->translatedFormat('j M Y') }} → {{ $sub->ends_at?->translatedFormat('j M Y') }}</div>
        </div>
        @if($sub->workoutPlan)
          <a href="{{ route('trainee.schedule', ['subscription' => $sub->id]) }}" class="btn btn-sm">{{ __('Workout Plan') }}</a>
        @endif
        @if($sub->nutritionPlan)
          <a href="{{ route('trainee.diet', ['subscription' => $sub->id]) }}" class="btn btn-sm">{{ __('Nutrition Plan') }}</a>
        @endif
        @if($sub->package_id)
          <a href="{{ route('coaches.show', $sub->coach_id) }}" class="btn btn-sm btn-primary"><i class="fas fa-redo"></i> {{ __('Renew') }}</a>
        @endif
      </div>
    @empty
      <p class="muted">{{ __('No previous subscriptions.') }}</p>
    @endforelse
  </section>
</div>
@endsection
