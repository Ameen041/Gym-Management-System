@extends('layouts.panel')
@section('title', __('Weekly check-in'))

@section('content')
@php
  use App\Models\CheckIn;
  $kg = fn ($w) => \App\Http\Controllers\CheckInController::kg($w);
  $lastWeight = $schedule['last']?->weight ?? ($subscription->intake['weight'] ?? null);
@endphp

<div class="page-title">
  <div>
    <span class="eyebrow">{{ $subscription->coach->name }}</span>
    <h1>{{ __('Weekly check-in') }}</h1>
  </div>
</div>

<div class="grid-2" style="align-items:start">
  <section class="panel">
    @if($canSend)
      <div class="panel-head"><h2><i class="fas fa-clipboard-check"></i> {{ __('How was your week?') }}</h2></div>
      <form method="POST" action="{{ route('check-ins.store', $subscription) }}" enctype="multipart/form-data">
        @csrf
        <div class="form-row">
          <div class="form-group">
            <label for="ci-weight">{{ __('Weight (kg)') }}</label>
            <input type="number" id="ci-weight" name="weight" step="0.1" min="20" max="300" inputmode="decimal" required
                   value="{{ old('weight') }}" placeholder="{{ $lastWeight ? $kg((float) $lastWeight) : '' }}">
          </div>
          <div class="form-group">
            <label for="ci-waist">{{ __('Waist (cm)') }} <span class="muted small">{{ __('optional') }}</span></label>
            <input type="number" id="ci-waist" name="waist" step="0.5" min="30" max="250" inputmode="decimal" value="{{ old('waist') }}">
          </div>
        </div>

        @if($subscription->includes_workout)
          <fieldset class="form-group">
            <legend class="label">{{ __('Workouts done this week') }}</legend>
            <div class="scale">
              @for($n = 0; $n <= 7; $n++)
                <label><input type="radio" name="workouts_done" value="{{ $n }}" @checked(old('workouts_done') === (string) $n)><span>{{ $n }}</span></label>
              @endfor
            </div>
          </fieldset>
        @endif

        @php
          $scales = [];
          if ($subscription->includes_nutrition) {
              $scales['diet_adherence'] = [__('How closely did you follow the diet?'), CheckIn::DIET_SCALE];
          }
          $scales['energy'] = [__('Energy this week'), CheckIn::FEEL_SCALE];
          $scales['sleep'] = [__('Sleep this week'), CheckIn::FEEL_SCALE];
        @endphp
        @foreach($scales as $field => [$question, $options])
          <fieldset class="form-group">
            <legend class="label">{{ $question }}</legend>
            <div class="scale-box">
              <div class="scale">
                @foreach($options as $n => $text)
                  <label title="{{ __($text) }}"><input type="radio" name="{{ $field }}" value="{{ $n }}" @checked(old($field) === (string) $n)><span>{{ $n }}</span></label>
                @endforeach
              </div>
              <div class="scale-ends"><span>{{ __($options[1]) }}</span><span>{{ __($options[5]) }}</span></div>
            </div>
          </fieldset>
        @endforeach

        <div class="form-group">
          <label for="ci-note">{{ __('Notes for your coach') }} <span class="muted small">{{ __('optional') }}</span></label>
          <textarea id="ci-note" name="note" rows="3" maxlength="2000" placeholder="{{ __('What went well, what was hard, any pain or questions') }}">{{ old('note') }}</textarea>
        </div>

        <div class="form-group">
          <label for="ci-photos">{{ __('Progress photos') }} <span class="muted small">{{ __('optional, up to 3') }}</span></label>
          <input type="file" id="ci-photos" name="photos[]" accept="image/*" multiple>
          <small class="muted">{{ __('Only your coach can see them.') }}</small>
        </div>

        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> {{ __('Send check-in') }}</button>
      </form>
    @elseif($subscription->isRunning())
      <div class="ci-next">
        <i class="fas fa-calendar-check"></i>
        <div>
          <strong>{{ __('Next check-in: :date', ['date' => $schedule['opens']->translatedFormat('l j M')]) }}</strong>
          <div class="muted small">{{ __('Once a week, your coach checks your progress and adjusts your plan.') }}</div>
        </div>
      </div>
    @else
      <p class="muted">{{ __('This subscription has ended.') }}</p>
    @endif
  </section>

  <section class="panel">
    <div class="panel-head"><h2><i class="fas fa-chart-line"></i> {{ __('Your progress') }}</h2></div>
    @include('check-ins._progress', ['points' => $points, 'goal' => $subscription->intake['goal'] ?? null])
  </section>
</div>

@if($checkIns->isNotEmpty())
  <section class="panel">
    <div class="panel-head"><h2><i class="fas fa-list"></i> {{ __('Your check-ins') }}</h2></div>
    @foreach($checkIns as $i => $checkIn)
      @include('check-ins._item', ['checkIn' => $checkIn, 'previous' => $checkIns[$i + 1] ?? null, 'coachView' => false])
    @endforeach
  </section>
@endif
@endsection
