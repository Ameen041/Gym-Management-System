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
          <div class="muted small">{{ $subscription->package_title }} · {{ $subscription->starts_at->translatedFormat('j M') }} {{ app()->isLocale('ar') ? '←' : '→' }} {{ $subscription->ends_at->translatedFormat('j M Y') }}</div>
          <div class="row-actions">
            @if($subscription->isUpcoming())
              <span class="badge badge-muted">{{ __('Starts :date', ['date' => $subscription->starts_at->translatedFormat('j M')]) }}</span>
            @elseif($subscription->isRunning())
              <span class="badge badge-ok">{{ __('Active') }}</span>
              @if($renewal?->status === 'active')
                <span class="badge badge-ok"><i class="fas fa-rotate-right"></i> {{ __('Renewed until :date', ['date' => $renewal->ends_at->translatedFormat('j M')]) }}</span>
              @elseif($renewal)
                <span class="badge badge-muted"><i class="fas fa-rotate-right"></i> {{ __('Renewing: waiting for payment') }}</span>
              @elseif($subscription->daysLeft() <= 7)
                <span class="badge badge-warn">{{ $subscription->daysLeft() === 0 ? __('Ends today') : trans_choice('{1} Ends tomorrow|[2,*] Ends in :count days', $subscription->daysLeft()) }}</span>
              @endif
            @else
              <span class="badge badge-muted">{{ __('Ended') }}</span>
            @endif
          </div>
        </div>
      </div>
      <a href="{{ route('chat.with', $trainee) }}" class="btn btn-primary btn-block" style="margin-top:14px">
        <i class="fas fa-comments"></i> {{ __('Chat') }}
        @if($chatUnread)<b class="count-pill" style="background:var(--on-accent);color:var(--accent-text)">{{ $chatUnread }}</b>@endif
      </a>
      @if($renewUrl)
        <a href="{{ $renewUrl }}" class="btn btn-block" style="margin-top:8px">
          <i class="fas fa-rotate-right"></i> {{ __('Invite to renew') }}
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
                @if($type === 'nutrition' && $plan->targets)
                  · {{ \App\Support\NutritionMath::number($plan->targets['calories']) }} {{ __('kcal') }}
                @endif
              @else
                {{ __('Due by :date', ['date' => $subscription->plan_due_at->translatedFormat('j M')]) }}
              @endif
            </div>
          </div>
          @if(!$plan && $lastPlans[$type])
            <form method="POST" action="{{ route('coach.plans.repeat', [$subscription, $type]) }}">
              @csrf
              <button type="submit" class="btn btn-sm btn-primary" title="{{ $lastPlans[$type]->title }}"><i class="fas fa-copy"></i> {{ __('Send last plan again') }}</button>
            </form>
            <a href="{{ route('coach.plans.edit', [$subscription, $type]) }}" class="btn btn-sm"><i class="fas fa-edit"></i> {{ __('Change it first') }}</a>
          @else
            <a href="{{ route('coach.plans.edit', [$subscription, $type]) }}" class="btn btn-sm {{ $plan ? '' : 'btn-primary' }}">
              <i class="fas {{ $plan ? 'fa-edit' : 'fa-plus' }}"></i> {{ $plan ? __('Update plan') : __('Create plan') }}
            </a>
          @endif
        </div>
      @endforeach
    </section>
  </div>

  <section class="panel" id="health">
    <div class="panel-head"><h2><i class="fas fa-user"></i> {{ __('Client answers') }}</h2></div>
    @if(isset($intake['health']))
      @if($healthFlags)
        <div class="alert health-alert">
          <i class="fas fa-heart-pulse" style="color:var(--warning)"></i>
          <div>
            <strong>{{ __('Health alert: the client answered "yes" to:') }}</strong>
            <ul>@foreach($healthFlags as $flag)<li>{{ __(\App\Models\Subscription::HEALTH_LABELS[$flag] ?? $flag) }}</li>@endforeach</ul>
            <div class="small" style="margin-top:6px">{{ __('Build the plan around it, keep it gentle at first, and ask for their doctor\'s approval. Don\'t give medical treatment or medicine.') }}</div>
          </div>
        </div>
      @else
        <p class="small" style="margin-bottom:8px"><i class="fas fa-circle-check" style="color:var(--success)"></i> {{ __('No health problems in the client\'s answers.') }}</p>
      @endif
    @endif
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
    @if(!empty($intake['food_notes']))
      <div class="list-row"><div class="grow"><div class="muted small">{{ __('Food allergies or foods they don\'t eat') }}</div><p style="white-space:pre-line">{{ $intake['food_notes'] }}</p></div></div>
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
            <a href="{{ $remindUrl }}" class="btn btn-sm"><i class="fas fa-comments"></i> {{ __('Remind in the chat') }}</a>
          @endif
        </div>
      @endif
    @endif
  </div>

  @include('check-ins._progress', ['points' => $points, 'goal' => $intake['goal'] ?? null])

  @if($checkIns->isNotEmpty())
    <h3 class="sub-title">{{ __('Check-ins') }}</h3>
    @foreach($checkIns as $i => $checkIn)
      @include('check-ins._item', ['checkIn' => $checkIn, 'previous' => $checkIns[$i + 1] ?? null, 'coachView' => true, 'target' => $subscription->intake['days_per_week'] ?? null])
    @endforeach
  @else
    <p class="muted small">{{ __('Your client sends a check-in once a week: weight, how the week went and photos. You reply here.') }}</p>
  @endif
</section>
@endsection
