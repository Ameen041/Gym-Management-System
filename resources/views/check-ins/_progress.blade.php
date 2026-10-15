{{-- Start / now / change + weight chart. $points (oldest first), $goal (intake goal key or null) --}}
@php
  $kg = fn ($w) => \App\Http\Controllers\CheckInController::kg($w);
  $start = $points[0]['weight'] ?? null;
  $now = count($points) ? end($points)['weight'] : null;
  $change = ($start !== null && $now !== null) ? round($now - $start, 1) : null;
  // which direction counts as progress depends on the goal
  $good = $change !== null && $change != 0 && (($goal === 'weight_loss' && $change < 0) || ($goal === 'muscle_gain' && $change > 0));
@endphp
@if(count($points) >= 2)
  <div class="progress-stats">
    <div><small>{{ __('Start') }}</small><b>{{ $kg($start) }}</b> <span class="muted small">{{ __('kg') }}</span></div>
    <div><small>{{ __('Now') }}</small><b>{{ $kg($now) }}</b> <span class="muted small">{{ __('kg') }}</span></div>
    <div>
      <small>{{ __('Change') }}</small>
      <b dir="ltr" class="{{ $good ? 'good' : '' }}">{{ $change > 0 ? '+' : '' }}{{ $kg($change) }}</b>
      @if($good)<i class="fas fa-circle-check good" title="{{ __('In the right direction for the goal') }}"></i>@endif
    </div>
  </div>
  @include('partials.weight-chart', ['points' => $points])
@else
  <p class="muted">{{ __('The weight chart appears after the first check-in.') }}</p>
@endif
