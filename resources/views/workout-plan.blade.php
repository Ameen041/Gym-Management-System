@extends('layouts.panel')
@section('title', __('Workout plan'))

@section('content')
@php
  use App\Models\Exercise;
  $details = json_decode($plan->plan_details, true);
  $details = is_array($details) ? $details : [];
  // Library exercises used in this plan, to link each one to its video page
  $libraryIds = collect($details)->flatten(1)->pluck('exercise_id')->filter()->unique();
  $library = Exercise::whereIn('id', $libraryIds)->get()->keyBy('id');
  $weekDays = \App\Http\Controllers\Coach\PlanController::WEEK_DAYS;
  $today = now()->format('l');
  $active = isset($details[$today]) ? $today : (array_key_first($details) ?? $today);
@endphp

@include('plans._header', ['plan' => $plan])

@if($details)
  <div class="daytabs" data-tabs role="tablist">
    @foreach($weekDays as $day)
      <button type="button" class="daytab {{ $day === $active ? 'on' : '' }} {{ isset($details[$day]) ? 'has' : '' }} {{ $day === $today ? 'is-today' : '' }}" data-tab="{{ $day }}" role="tab">
        {{ __(substr($day, 0, 3)) }}<b>{{ isset($details[$day]) ? count($details[$day]) : '–' }}</b>
      </button>
    @endforeach
  </div>

  @foreach($weekDays as $day)
    @php($exercises = $details[$day] ?? [])
    <section class="plan-day" data-panel="{{ $day }}" @if($day !== $active) hidden @endif>
      <h2 class="plan-day-title">
        {{ __($day) }} @if($day === $today)<span class="badge">{{ __('Today') }}</span>@endif
        @if($exercises)
          <small>{{ collect($exercises)->map(fn ($e) => Exercise::translateMuscleName($e['muscle'] ?? ''))->filter()->unique()->implode('، ') }}</small>
        @endif
      </h2>
      @forelse($exercises as $exercise)
        @php($ex = $library[$exercise['exercise_id'] ?? 0] ?? null)
        <article class="ex-card">
          <span class="ex-num">{{ $loop->iteration }}</span>
          <span class="pick-icon">@if($ex?->thumbnail)<img src="{{ $ex->thumbnail }}" alt="" loading="lazy">@else<i class="fas fa-dumbbell"></i>@endif</span>
          <div class="ex-info">
            @if($ex)
              <a href="{{ route('exercises.show', $ex) }}" class="ex-name">{{ $ex->display_name }}</a>
            @else
              <span class="ex-name">{{ $exercise['exercise'] ?? '' }}</span>
            @endif
            <span class="muted small">{{ Exercise::translateMuscleName($exercise['muscle'] ?? '') }}</span>
          </div>
          <div class="ex-sr">
            <span><b>{{ $exercise['sets'] ?? '' }}</b><small>{{ __('Sets') }}</small></span>
            <span class="wb-x">×</span>
            <span><b>{{ $exercise['reps'] ?? '' }}</b><small>{{ __('Reps') }}</small></span>
          </div>
          @if($ex)
            <a href="{{ route('exercises.show', $ex) }}" class="icon-btn ex-play" title="{{ __('How to perform') }}" aria-label="{{ __('How to perform') }}"><i class="fas fa-play"></i></a>
          @endif
        </article>
      @empty
        <div class="builder-rest"><i class="fas fa-bed"></i> {{ __('Rest day') }}</div>
      @endforelse
    </section>
  @endforeach
@else
  <div class="panel empty"><i class="fas fa-triangle-exclamation"></i><p>{{ __('No valid workout plan data available.') }}</p></div>
@endif
@include('plans._disclaimer')
@endsection
