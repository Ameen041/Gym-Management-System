@extends('layouts.panel')
@section('title', __('Coach Dashboard'))

@section('content')
@php use App\Support\Money; @endphp

<div class="page-title">
  <div>
    <span class="eyebrow">{{ now()->translatedFormat('l j F') }}</span>
    <h1>{{ __('Hi :name', ['name' => \Illuminate\Support\Str::before($coach->name, ' ')]) }}</h1>
  </div>
</div>

{{-- Profile status --}}
@if($profile->status === 'rejected')
  <div class="alert alert-error">
    <i class="fas fa-times-circle"></i>
    <div>
      <strong>{{ __('Your profile was not approved.') }}</strong>
      @if($profile->rejection_reason)<div>{{ $profile->rejection_reason }}</div>@endif
      <div>{{ __('Update your profile and it will be reviewed again.') }}</div>
    </div>
  </div>
@elseif($profile->status === 'pending' || $missing)
  <div class="alert alert-warning">
    <i class="fas fa-user-clock"></i>
    <div>
      @if($missing)
        <strong>{{ __('Complete your profile to appear on the coaches page:') }}</strong>
        <ul>
          @foreach($missing as $item)<li>{{ $item }}</li>@endforeach
        </ul>
      @else
        <strong>{{ __('Your profile is under review.') }}</strong>
        <div>{{ __('We will approve it soon. You will then appear on the coaches page.') }}</div>
      @endif
    </div>
  </div>
@else
  <div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <div>
      {{ __('Your profile is live on the coaches page.') }}
      <a href="{{ route('coaches.show', $coach) }}">{{ __('View my public profile') }}</a>
    </div>
  </div>
@endif

<div class="stats">
  <div class="stat"><div class="label">{{ __('Active clients') }}</div><div class="value">{{ $clients->count() }}</div></div>
  <div class="stat"><div class="label">{{ __('Waiting for a plan') }}</div><div class="value" style="color:{{ $waitingPlans ? 'var(--danger)' : 'inherit' }}">{{ $waitingPlans }}</div></div>
  <div class="stat"><div class="label">{{ __('Ready to be paid') }}</div><div class="value">{{ Money::format($earnings['available']) }}</div></div>
  <div class="stat"><div class="label">{{ __('Rating') }}</div><div class="value" style="font-size:1rem;margin-top:8px">@include('partials.stars', ['rating' => $profile->rating_avg, 'count' => $profile->rating_count])</div></div>
</div>

<div class="grid-2" style="align-items:start">
  <section class="panel">
    <div class="panel-head"><h2><i class="fas fa-users"></i> {{ __('Active clients') }}</h2></div>
    @forelse($clients as $sub)
      @php($missingPlans = $sub->missingPlans())
      <div class="list-row">
        <img src="{{ $sub->trainee->photo_url }}" alt="" class="avatar-sm">
        <div class="grow">
          <strong>{{ $sub->trainee->name }}</strong>
          <div class="muted small">{{ $sub->package_title }} · {{ __('until :date', ['date' => $sub->ends_at->translatedFormat('j M')]) }}</div>
          @if($missingPlans)
            <span class="badge {{ $sub->plan_due_at->isPast() ? 'badge-danger' : 'badge-warn' }}">
              <i class="fas fa-hourglass-half"></i> {{ __('Plan due by :date', ['date' => $sub->plan_due_at->translatedFormat('j M')]) }}
            </span>
          @else
            <span class="badge badge-ok"><i class="fas fa-check"></i> {{ __('Plan sent') }}</span>
          @endif
        </div>
        <a href="{{ route('coach.clients.show', $sub) }}" class="btn btn-sm btn-primary">{{ __('Open') }}</a>
      </div>
    @empty
      <div class="empty">
        <i class="fas fa-user-friends"></i>
        <p>{{ __('No active clients yet. Clients appear here once their payment is confirmed.') }}</p>
      </div>
    @endforelse
  </section>

  <div class="stack">
    <section class="panel">
      <div class="panel-head"><h2><i class="fas fa-wallet"></i> {{ __('Earnings') }}</h2></div>
      <div class="list-row"><span class="grow">{{ __('Total earned (after commission)') }}</span><strong>{{ Money::format($earnings['earned']) }}</strong></div>
      <div class="list-row"><span class="grow">{{ __('Already paid to you') }}</span><strong>{{ Money::format($earnings['paid']) }}</strong></div>
      <div class="list-row"><span class="grow">{{ __('On hold') }}</span><strong>{{ Money::format($earnings['pending']) }}</strong></div>
      <div class="list-row"><span class="grow">{{ __('Ready to be paid') }}</span><strong style="color:var(--primary)">{{ Money::format($earnings['available']) }}</strong></div>
      <p class="muted small" style="margin-top:8px">{{ __('The money of each client becomes ready :days days after the payment is confirmed, once you have sent the plan. The platform sends ready earnings to your Sham Cash account every week.', ['days' => $earnings['hold_days']]) }}</p>
    </section>

    <section class="panel">
      <div class="panel-head"><h2><i class="fas fa-history"></i> {{ __('Recently ended') }}</h2></div>
      @forelse($recent as $sub)
        <div class="list-row">
          <div class="grow"><strong>{{ $sub->trainee->name }}</strong> <span class="muted small">· {{ $sub->ends_at->translatedFormat('j M Y') }}</span></div>
        </div>
      @empty
        <p class="muted">{{ __('Nothing yet.') }}</p>
      @endforelse
    </section>
  </div>
</div>
@endsection
