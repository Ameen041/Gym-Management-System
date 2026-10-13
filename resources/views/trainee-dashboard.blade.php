@extends('layouts.panel')
@section('title', __('Trainee Dashboard'))

@section('content')
@php use App\Support\Money; @endphp

<div class="page-title">
  <h1>{{ __('Welcome back, :name', ['name' => $user->name]) }} 👋</h1>
  <p>{{ __('Your coaching subscriptions and plans in one place.') }}</p>
</div>

{{-- Running subscriptions --}}
@forelse($running as $sub)
  <section class="panel">
    <div class="panel-head">
      <div class="profile-head" style="gap:12px">
        <img src="{{ $sub->coach->photo_url }}" alt="" class="avatar-sm">
        <div>
          <h2 style="font-size:1.1rem">{{ $sub->coach->name }}</h2>
          <div class="muted small">{{ $sub->package_title }}</div>
        </div>
      </div>
      <span class="badge badge-ok"><i class="fas fa-check-circle"></i> {{ __('Active until :date', ['date' => $sub->ends_at->translatedFormat('j M Y')]) }}</span>
    </div>

    <div class="grid-2">
      <div class="stack">
        @if($sub->includes_workout)
          @if($sub->workoutPlan)
            <a href="{{ route('trainee.schedule', ['subscription' => $sub->id]) }}" class="btn btn-primary btn-block"><i class="fas fa-dumbbell"></i> {{ __('View Workout Plan') }}</a>
          @else
            <div class="alert alert-warning" style="margin:0"><i class="fas fa-hourglass-half"></i><div>{{ __('Your coach is preparing your workout plan (expected by :date).', ['date' => $sub->plan_due_at->translatedFormat('j M')]) }}</div></div>
          @endif
        @endif
        @if($sub->includes_nutrition)
          @if($sub->nutritionPlan)
            <a href="{{ route('trainee.diet', ['subscription' => $sub->id]) }}" class="btn btn-primary btn-block"><i class="fas fa-apple-alt"></i> {{ __('View Nutrition Plan') }}</a>
          @else
            <div class="alert alert-warning" style="margin:0"><i class="fas fa-hourglass-half"></i><div>{{ __('Your coach is preparing your nutrition plan (expected by :date).', ['date' => $sub->plan_due_at->translatedFormat('j M')]) }}</div></div>
          @endif
        @endif
        @if($sub->coach->whatsapp_url)
          <a href="{{ $sub->coach->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-whatsapp btn-block"><i class="fab fa-whatsapp"></i> {{ __('Chat with your coach on WhatsApp') }}</a>
        @endif
      </div>

      <div>
        @if($sub->review)
          <div class="muted small">{{ __('Your review') }}</div>
          @include('partials.stars', ['rating' => $sub->review->rating, 'count' => null])
          @if($sub->review->comment)<p class="small" style="margin-top:4px">{{ $sub->review->comment }}</p>@endif
        @else
          <form method="POST" action="{{ route('subscriptions.review', $sub) }}">
            @csrf
            <div class="form-group">
              <label>{{ __('Rate your coach') }}</label>
              <select name="rating" required>
                @for($r = 5; $r >= 1; $r--)
                  <option value="{{ $r }}">{{ str_repeat('★', $r) }} ({{ $r }})</option>
                @endfor
              </select>
            </div>
            <div class="form-group">
              <textarea name="comment" rows="2" placeholder="{{ __('Write a short review (optional)') }}"></textarea>
            </div>
            <button class="btn btn-sm"><i class="fas fa-star"></i> {{ __('Send review') }}</button>
          </form>
        @endif
      </div>
    </div>
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

<div class="grid-2" style="margin-top:16px;align-items:start">
  {{-- Profile --}}
  <section class="panel">
    <div class="panel-head"><h2><i class="fas fa-user"></i> {{ __('Your Profile') }}</h2></div>
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
  </section>

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
