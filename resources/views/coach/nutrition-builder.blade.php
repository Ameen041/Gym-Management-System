@extends('layouts.panel')
@section('title', __('Nutrition plan') . ' · ' . $subscription->trainee->name)

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/builder.css') }}">
@endpush

@section('content')
@php
  use App\Models\Food;
  use App\Support\NutritionMath;
  // After a validation error, show what the coach typed instead of losing it
  if (is_array(old('meals'))) {
      $days = NutritionMath::builderDaysFromInput(old('meals'), $weekDays);
  }
  $foodData = $foods->map(fn ($f) => [
      'id' => $f->id, 'n' => $f->display_name, 'en' => $f->name, 'ar' => $f->name_ar, 'cat' => $f->category,
      'cal' => $f->calories, 'p' => $f->protein, 'c' => $f->carbs, 'f' => $f->fat,
      'sg' => $f->serving_grams, 'sn' => $f->display_serving,
  ])->values();
  $categoryCounts = $foods->countBy('category');
  $mealIndex = 0;
  $targets = (array) old('targets', $targets);
  $goal = \App\Http\Controllers\SubscriptionController::GOALS[$subscription->intake['goal'] ?? ''] ?? null;
  $macroLabels = ['calories' => __('Calories'), 'protein' => __('Protein'), 'carbs' => __('Carbs'), 'fat' => __('Fat')];
  $firstDay = collect($weekDays)->first(fn ($d) => !empty($days[$d])) ?? $weekDays[0];
@endphp

<form method="POST" action="{{ route('coach.plans.save', [$subscription, 'nutrition']) }}" id="nutrition-form" class="builder" data-first-day="{{ $firstDay }}">
  @csrf

  @if($draftFrom)
    <div class="alert" role="status">
      <i class="fas fa-copy"></i>
      <div>{{ __('Started from the last plan (:date). Change what you need, then send.', ['date' => $draftFrom->updated_at->translatedFormat('j M')]) }}</div>
    </div>
  @endif

  <div class="builder-top">
    <div class="builder-who">
      <img src="{{ $subscription->trainee->photo_url }}" alt="" class="avatar-sm">
      <div class="grow">
        <input type="text" name="title" required maxlength="255" class="builder-title" value="{{ old('title', $plan->title ?? $draftFrom->title ?? '') }}"
               placeholder="{{ __('Plan name, e.g. Cutting - Week 1') }}" aria-label="{{ __('Plan Title') }}">
        <span class="muted small">{{ $subscription->trainee->name }} · {{ $subscription->package_title }}</span>
      </div>
    </div>
    @if($templates->count())
      <select class="builder-template" data-template aria-label="{{ __('Load Nutrition Template') }}">
        <option value="">{{ __('Template') }}</option>
        @foreach($templates as $tpl)
          <option value="{{ $tpl->id }}" data-body="{{ trim($tpl->plan_details) }}">{{ $tpl->title }}</option>
        @endforeach
      </select>
    @endif
  </div>

  {{-- Daily targets come first: the days are filled until each bar is in its range --}}
  <section class="panel nb-targets" data-targets data-weight="{{ $weight ?: '' }}">
    <div class="panel-head">
      <h2><i class="fas fa-bullseye"></i> {{ __('Daily targets') }}</h2>
      @if($weight || $goal)
        <span class="muted small">
          @if($weight){{ __('Weight') }}: {{ NutritionMath::number($weight) }} {{ __('kg') }}@endif
          @if($weight && $goal) · @endif
          @if($goal){{ __($goal) }}@endif
        </span>
      @endif
    </div>
    <p class="muted small nb-targets-help">{{ __('Start here: how much should your client eat each day? Then fill the days until every bar is in its range.') }}</p>
    <div class="nb-target-grid">
      @foreach($macroLabels as $macro => $label)
        @php([$min, $max] = NutritionMath::TARGET_LIMITS[$macro])
        <label class="nb-target {{ $macro }}">
          <span class="nb-target-label">{{ $label }}</span>
          <span class="nb-target-input">
            <input type="number" name="targets[{{ $macro }}]" value="{{ $targets[$macro] ?? '' }}" min="{{ $min }}" max="{{ $max }}"
                   step="{{ $macro === 'calories' ? 10 : 1 }}" inputmode="numeric" required data-target="{{ $macro }}">
            <small>{{ $macro === 'calories' ? __('kcal') : __('g') }}</small>
          </span>
          <small class="nb-target-hint" data-target-hint="{{ $macro }}"></small>
        </label>
      @endforeach
    </div>
    <div class="nb-target-check" data-target-check hidden>
      <i class="fas fa-circle-info"></i>
      <span class="grow" data-target-check-text></span>
      <button type="button" class="btn btn-sm" data-fill-carbs><i class="fas fa-wand-magic-sparkles"></i> {{ __('Carbs make up the rest') }}</button>
    </div>
  </section>

  <div class="daytabs" role="tablist">
    @foreach($weekDays as $day)
      <button type="button" class="daytab" role="tab" data-tab="{{ $day }}">{{ __(substr($day, 0, 3)) }}<b data-tab-count>–</b></button>
    @endforeach
  </div>

  @foreach($weekDays as $day)
    <section class="builder-day nb-day" data-day="{{ $day }}" data-day-label="{{ __($day) }}" role="tabpanel" hidden>
      <div class="builder-day-head">
        <h2>{{ __($day) }}</h2>
        <select class="builder-copy" data-copy aria-label="{{ __('Copy this day') }}">
          <option value="">{{ __('Copy to…') }}</option>
          <option value="*">{{ __('All days') }}</option>
          @foreach($weekDays as $other)
            @if($other !== $day)<option value="{{ $other }}">{{ __($other) }}</option>@endif
          @endforeach
        </select>
      </div>

      <div class="nb-range" data-day-total>
        @foreach($macroLabels as $macro => $label)
          <div class="range-row {{ $macro }}" data-range="{{ $macro }}">
            <span class="range-label">{{ $label }}</span>
            <span class="range-track"><span class="range-zone"></span><span class="range-fill"></span></span>
            <span class="range-v"><span><b data-v="{{ $macro }}">0</b><span data-of></span></span><small data-left></small></span>
          </div>
        @endforeach
      </div>

      <div class="nb-meals" data-meals>
        @foreach($days[$day] ?? [] as $meal)
          @include('coach._nb-meal', ['mk' => 'm' . $mealIndex++, 'day' => $day, 'meal' => $meal])
        @endforeach
      </div>
      <div class="builder-rest"><i class="fas fa-mug-hot"></i> {{ __('No meals yet') }}</div>
      <button type="button" class="builder-add" data-add-meal><i class="fas fa-plus"></i> {{ __('Add meal') }}</button>
    </section>
  @endforeach

  <details class="panel nb-week">
    <summary><i class="fas fa-chart-simple"></i> {{ __('Week summary') }}</summary>
    <div class="table-wrap" style="margin-top:12px">
      <table class="table">
        <thead><tr><th>{{ __('Day') }}</th><th>{{ __('kcal') }}</th><th>{{ __('Protein') }}</th><th>{{ __('Carbs') }}</th><th>{{ __('Fat') }}</th></tr></thead>
        <tbody>
          @foreach($weekDays as $day)
            <tr data-summary-day="{{ $day }}"><td>{{ __($day) }}</td><td data-v="calories">0</td><td data-v="protein">0</td><td data-v="carbs">0</td><td data-v="fat">0</td></tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr data-summary-avg><th>{{ __('Daily average') }}</th><th data-v="calories">0</th><th data-v="protein">0</th><th data-v="carbs">0</th><th data-v="fat">0</th></tr>
          <tr data-summary-target><th>{{ __('Daily target') }}</th><th data-v="calories">–</th><th data-v="protein">–</th><th data-v="carbs">–</th><th data-v="fat">–</th></tr>
        </tfoot>
      </table>
    </div>
  </details>

  <div class="dock">
    <div class="dock-info"><b data-dock-day></b><span data-dock-sub></span></div>
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> {{ $plan ? __('Save') : __('Send') }}</button>
  </div>
</form>

{{-- Food picker --}}
<aside class="sheet" id="picker" aria-hidden="true" aria-label="{{ __('Add food') }}">
  <div class="sheet-head">
    <h3>{{ __('Add food') }} · <span data-picker-target></span></h3>
    <button type="button" class="icon-btn" data-close-picker aria-label="{{ __('Close') }}"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="sheet-tools">
    <div class="search-box">
      <i class="fas fa-magnifying-glass"></i>
      <input type="search" data-search placeholder="{{ __('Search: egg, rice, chicken…') }}" autocomplete="off" aria-label="{{ __('Search') }}">
    </div>
    <div class="chips" data-cats>
      <button type="button" class="chip on" data-cat="">{{ __('All') }}</button>
      @foreach(Food::CATEGORIES as $key => $label)
        @if($categoryCounts[$key] ?? 0)
          <button type="button" class="chip" data-cat="{{ $key }}">{{ __($label) }}</button>
        @endif
      @endforeach
    </div>
  </div>
  <div class="sheet-body" data-results></div>
  <div class="sheet-foot">
    <span class="grow muted small" data-picker-count></span>
    <button type="button" class="btn btn-primary" data-close-picker>{{ __('Done') }}</button>
  </div>
</aside>
<div class="sheet-backdrop" data-close-picker></div>

<template id="nb-meal-template">@include('coach._nb-meal', ['mk' => '__M__', 'day' => '__DAY__', 'meal' => []])</template>
<template id="nb-item-template">@include('coach._nb-item', ['mk' => '__M__', 'ik' => '__I__', 'item' => []])</template>
@endsection

@push('scripts')
<script>
  window.NB = {
    foods: @json($foodData),
    i18n: {
      kcal: @js(__('kcal')), g: @js(__('g')), serving: @js(__('serving')),
      p: @js(__('P')), c: @js(__('C')), f: @js(__('F')),
      per100: @js(__('per 100 g')),
      noMeals: @js(__('No meals yet')),
      meals: @js(__(':count meals')),
      meal: @js(__('Meal')),
      inMeal: @js(__('In this meal')),
      noResults: @js(__('No food found.')),
      addCustom: @js(__('Add ":name" with your own values')),
      removeMeal: @js(__('Remove this meal and its foods?')),
      copyConfirm: @js(__('Replace the meals of :target with the meals of :day?')),
      allDays: @js(__('all days')),
      templateConfirm: @js(__('Loading the template replaces the meals you added. Continue?')),
      avg: @js(__('Daily average')),
      left: @js(__(':n left')),
      over: @js(__(':n over')),
      perKg: @js(__(':n per kg')),
      ofKcal: @js(__(':n% of calories')),
      macrosGive: @js(__('Protein, carbs and fat add up to :kcal kcal, but the target is :target kcal.')),
      offTarget: @js(__('These days are not on target yet: :days. Send the plan anyway?')),
    },
    range: @json(NutritionMath::TARGET_RANGE),
  };
</script>
<script src="{{ \App\Support\Asset::url('js/nutrition-builder.js') }}"></script>
@endpush
