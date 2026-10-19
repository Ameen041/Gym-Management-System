@extends('layouts.panel')
@section('title', __('Nutrition plan'))

@section('content')
@php
  use App\Support\NutritionMath;
  $details = json_decode($plan->plan_details, true);
  $details = is_array($details) ? $details : [];
  $weekDays = \App\Http\Controllers\Coach\PlanController::WEEK_DAYS;
  $today = now()->format('l');
  $active = isset($details[$today]) ? $today : (array_key_first($details) ?? $today);
  $macroLabels = ['protein' => __('Protein'), 'carbs' => __('Carbs'), 'fat' => __('Fat')];
  $targets = $plan->targets;
@endphp

@include('plans._header', ['plan' => $plan])

@if($targets)
  <div class="target-strip">
    <i class="fas fa-bullseye"></i>
    <div class="grow">
      <b>{{ __('Your daily target') }}</b>
      <span>
        <strong>{{ NutritionMath::number($targets['calories']) }}</strong> {{ __('kcal') }}
        @foreach($macroLabels as $macro => $label)
          · {{ $label }} <strong>{{ NutritionMath::number($targets[$macro] ?? 0) }}</strong> {{ __('g') }}
        @endforeach
      </span>
    </div>
  </div>
@endif

@if($details)
  <div class="daytabs" data-tabs role="tablist">
    @foreach($weekDays as $day)
      @php($dayTotal = isset($details[$day]) ? NutritionMath::sum((array) $details[$day]) : null)
      <button type="button" class="daytab {{ $day === $active ? 'on' : '' }} {{ $dayTotal ? 'has' : '' }} {{ $day === $today ? 'is-today' : '' }}" data-tab="{{ $day }}" role="tab">
        {{ __(substr($day, 0, 3)) }}<b>{{ $dayTotal ? round($dayTotal['calories']) : '–' }}</b>
      </button>
    @endforeach
  </div>

  @foreach($weekDays as $day)
    @php($meals = (array) ($details[$day] ?? []))
    <section class="plan-day" data-panel="{{ $day }}" @if($day !== $active) hidden @endif>
      <h2 class="plan-day-title">{{ __($day) }} @if($day === $today)<span class="badge">{{ __('Today') }}</span>@endif</h2>
      @if($meals)
        @php($total = NutritionMath::sum($meals))
        @php($kcalFrom = ['protein' => $total['protein'] * 4, 'carbs' => $total['carbs'] * 4, 'fat' => $total['fat'] * 9])
        @php($all = array_sum($kcalFrom) ?: 1)
        <div class="nb-daytotal">
          <div class="kcal-big">{{ round($total['calories']) }} <small>{{ __('kcal') }}</small></div>
          <div class="macros">
            @foreach($macroLabels as $macro => $label)
              <div class="macro {{ $macro }}"><span>{{ $label }}</span><span class="track"><span class="fill" style="width:{{ round($kcalFrom[$macro] / $all * 100) }}%"></span></span><span class="v">{{ NutritionMath::number($total[$macro]) }} {{ __('g') }}</span></div>
            @endforeach
          </div>
        </div>

        @foreach($meals as $meal)
          <article class="meal-card">
            <header>
              <b>{{ __('Meal') }} {{ $meal['meal_number'] ?? $loop->iteration }}</b>
              @if(isset($meal['items']) && filled($meal['description'] ?? null))<span class="muted">{{ $meal['description'] }}</span>@endif
              <span class="meal-kcal">{{ round((float) ($meal['calories'] ?? 0)) }} {{ __('kcal') }}</span>
            </header>
            @if(isset($meal['items']))
              <ul class="meal-items">
                @foreach($meal['items'] as $item)
                  <li>
                    <span class="grow">{{ NutritionMath::itemName($item) }}</span>
                    <span class="muted small">{{ NutritionMath::itemAmount($item) }}</span>
                    <span class="meal-item-kcal">{{ round((float) ($item['calories'] ?? 0)) }}</span>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="meal-legacy">{{ $meal['description'] ?? '' }}</p>
            @endif
            <footer class="muted small">
              {{ __('P') }} {{ NutritionMath::number($meal['protein'] ?? 0) }} · {{ __('C') }} {{ NutritionMath::number($meal['carbs'] ?? 0) }} · {{ __('F') }} {{ NutritionMath::number($meal['fat'] ?? 0) }}
            </footer>
          </article>
        @endforeach
      @else
        <div class="builder-rest"><i class="fas fa-mug-hot"></i> {{ __('No meals for this day') }}</div>
      @endif
    </section>
  @endforeach
@else
  <div class="panel empty"><i class="fas fa-triangle-exclamation"></i><p>{{ __('No valid nutrition plan data available.') }}</p></div>
@endif
@include('plans._disclaimer')
@endsection
