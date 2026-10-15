@extends('layouts.panel')
@section('title', $subscription->trainee->name)

@section('content')
@php
  use App\Http\Controllers\SubscriptionController as Sub;
  $trainee = $subscription->trainee;
  $intake = $subscription->intake ?? [];
@endphp

<div class="grid-2" style="align-items:start">
  <div class="stack">
    <section class="panel">
      <div class="profile-head">
        <img src="{{ $trainee->photo_url }}" alt="" class="avatar-lg" style="width:80px;height:80px">
        <div class="stack" style="flex:1">
          <h1 style="font-size:1.3rem">{{ $trainee->name }}</h1>
          <div class="muted small">{{ $subscription->package_title }} · {{ $subscription->starts_at->translatedFormat('j M') }} → {{ $subscription->ends_at->translatedFormat('j M Y') }}</div>
          <div>
            @if($subscription->isRunning())
              <span class="badge badge-ok">{{ __('Active') }}</span>
            @else
              <span class="badge badge-muted">{{ __('Ended') }}</span>
            @endif
          </div>
        </div>
      </div>
      @if($trainee->whatsapp_url)
        <a href="{{ $trainee->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-whatsapp btn-block" style="margin-top:14px">
          <i class="fab fa-whatsapp"></i> {{ __('Message on WhatsApp') }} <span dir="ltr">({{ $trainee->phone }})</span>
        </a>
      @endif
    </section>

    <section class="panel">
      <div class="panel-head"><h2><i class="fas fa-clipboard-list"></i> {{ __('Plans') }}</h2></div>
      @foreach(['workout' => ['fa-dumbbell', __('Workout plan'), $subscription->workoutPlan, $subscription->includes_workout],
                'nutrition' => ['fa-apple-alt', __('Nutrition plan'), $subscription->nutritionPlan, $subscription->includes_nutrition]] as $type => [$icon, $label, $plan, $included])
        @continue(!$included)
        <div class="list-row">
          <i class="fas {{ $icon }}" style="color:var(--primary)"></i>
          <div class="grow">
            <strong>{{ $label }}</strong>
            <div class="muted small">
              @if($plan)
                {{ __('Sent') }} · {{ $plan->updated_at->translatedFormat('j M Y') }}
              @else
                {{ __('Due by :date', ['date' => $subscription->plan_due_at->translatedFormat('j M')]) }}
              @endif
            </div>
          </div>
          <a href="{{ route('coach.plans.edit', [$subscription, $type]) }}" class="btn btn-sm {{ $plan ? '' : 'btn-primary' }}">
            <i class="fas {{ $plan ? 'fa-edit' : 'fa-plus' }}"></i> {{ $plan ? __('Update plan') : __('Create plan') }}
          </a>
        </div>
      @endforeach
    </section>
  </div>

  <section class="panel">
    <div class="panel-head"><h2><i class="fas fa-user"></i> {{ __('Client answers') }}</h2></div>
    <div class="list-row"><span class="grow muted">{{ __('Main goal') }}</span><strong>{{ __(Sub::GOALS[$intake['goal'] ?? ''] ?? '—') }}</strong></div>
    <div class="list-row"><span class="grow muted">{{ __('Training experience') }}</span><strong>{{ __(Sub::LEVELS[$intake['level'] ?? ''] ?? '—') }}</strong></div>
    <div class="list-row"><span class="grow muted">{{ __('Training days per week') }}</span><strong>{{ $intake['days_per_week'] ?? '—' }}</strong></div>
    <div class="list-row"><span class="grow muted">{{ __('Age') }} / {{ __('Height (cm)') }} / {{ __('Weight (kg)') }}</span><strong>{{ $intake['age'] ?? '—' }} / {{ $intake['height'] ?? '—' }} / {{ $intake['weight'] ?? '—' }}</strong></div>
    @if(!empty($intake['gym']))
      <div class="list-row"><span class="grow muted">{{ __('Gym') }}</span><strong>{{ $intake['gym'] }}</strong></div>
    @endif
    @if(!empty($intake['goal_details']))
      <div class="list-row"><div class="grow"><div class="muted small">{{ __('About the goal') }}</div><p style="white-space:pre-line">{{ $intake['goal_details'] }}</p></div></div>
    @endif
    @if(!empty($intake['health_notes']))
      <div class="list-row"><div class="grow"><div class="muted small">{{ __('Injuries or health notes') }}</div><p style="white-space:pre-line">{{ $intake['health_notes'] }}</p></div></div>
    @endif
    @if(!empty($subscription->body_photos))
      <div style="margin-top:12px">
        <div class="muted small" style="margin-bottom:6px">{{ __('Body photos') }}</div>
        <div class="thumbs">
          @foreach($subscription->photoUrls() as $url)
            <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt=""></a>
          @endforeach
        </div>
      </div>
    @endif
    @if($previous->isNotEmpty())
      <p class="muted small" style="margin-top:12px"><i class="fas fa-redo"></i> {{ __('Returning client: :count previous subscriptions.', ['count' => $previous->count()]) }}</p>
    @endif
  </section>
</div>

<section class="panel" id="progress" style="margin-top:14px">
  <div class="panel-head">
    <h2><i class="fas fa-chart-line"></i> {{ __('Progress') }}</h2>
    @if($subscription->isRunning())
      @if($lateDays === null)
        <span class="muted small"><i class="fas fa-calendar-check"></i> {{ __('Next check-in: :date', ['date' => $schedule['due']->translatedFormat('l j M')]) }}</span>
      @else
        <div class="row-actions">
          <span class="badge badge-warn"><i class="fas fa-clock"></i>
            {{ $lateDays === 0 ? __('Check-in due today') : trans_choice('{1} Check-in late by 1 day|[2,*] Check-in late by :count days', $lateDays) }}
          </span>
          @if($remindUrl)
            <a href="{{ $remindUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-whatsapp"><i class="fab fa-whatsapp"></i> {{ __('Remind on WhatsApp') }}</a>
          @endif
        </div>
      @endif
    @endif
  </div>

  @include('check-ins._progress', ['points' => $points, 'goal' => $intake['goal'] ?? null])

  @if($checkIns->isNotEmpty())
    <h3 class="sub-title">{{ __('Check-ins') }}</h3>
    @foreach($checkIns as $i => $checkIn)
      @include('check-ins._item', ['checkIn' => $checkIn, 'previous' => $checkIns[$i + 1] ?? null, 'coachView' => true])
    @endforeach
  @else
    <p class="muted small">{{ __('Your client sends a check-in once a week: weight, how the week went and photos. You reply here.') }}</p>
  @endif
</section>
@endsection
