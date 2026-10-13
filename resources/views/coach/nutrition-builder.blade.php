@extends('layouts.panel')
@section('title', ($plan ? __('Update plan') : __('Create plan')) . ': ' . __('Nutrition plan'))

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/nutrition-builder.css') }}">
@endpush

@section('content')
@php
  use App\Support\NutritionMath;
  // After a validation error, show what the coach typed instead of losing it
  if (is_array(old('meals'))) {
      $days = NutritionMath::builderDaysFromInput(old('meals'), $weekDays);
  }
  $foodData = $foods->map(fn ($f) => [
      'id' => $f->id, 'n' => $f->display_name, 'en' => $f->name, 'ar' => $f->name_ar,
      'cal' => $f->calories, 'p' => $f->protein, 'c' => $f->carbs, 'f' => $f->fat,
      'sg' => $f->serving_grams, 'sn' => $f->display_serving,
  ])->values();
  $mealIndex = 0;
@endphp

<div class="page-title nb-title">
  <div>
    <h1><i class="fas fa-apple-alt"></i> {{ $plan ? __('Update plan') : __('Create plan') }}: {{ __('Nutrition plan') }}</h1>
    <p>{{ __('For :name', ['name' => $subscription->trainee->name]) }} · {{ $subscription->package_title }}</p>
  </div>
  <a href="{{ route('coach.clients.show', $subscription) }}" class="btn btn-light"><i class="fas fa-arrow-left"></i> {{ __('Back to client') }}</a>
</div>

<form method="POST" action="{{ route('coach.plans.save', [$subscription, 'nutrition']) }}" id="nutrition-form">
  @csrf

  <section class="panel">
    <div class="form-row">
      <div class="form-group">
        <label>{{ __('Plan Title') }}</label>
        <input type="text" name="title" required maxlength="255" value="{{ old('title', $plan->title ?? '') }}" placeholder="{{ __('e.g. Cutting Plan - Week 1') }}">
      </div>
      @if($templates->count())
        <div class="form-group">
          <label>{{ __('Load Nutrition Template') }}</label>
          <select data-template>
            <option value="">{{ __('-- Select Template --') }}</option>
            @foreach($templates as $tpl)
              <option value="{{ $tpl->id }}" data-body="{{ trim($tpl->plan_details) }}">{{ $tpl->title }}</option>
            @endforeach
          </select>
        </div>
      @endif
    </div>
    <div class="alert" style="margin:0">
      <i class="fas fa-calculator"></i>
      <div>
        {{ __('Type a food and pick it from the list, then enter the amount: calories, protein, carbs and fat are calculated for the meal and the whole day.') }}
        <span class="muted">{{ __('For a food that is not in the list, type its name and enter its values yourself.') }}</span>
      </div>
    </div>
  </section>

  <div class="nb-days">
    @foreach($weekDays as $day)
      <section class="panel nb-day" data-day="{{ $day }}" data-day-label="{{ __($day) }}">
        <div class="nb-day-head">
          <h2><i class="fas fa-calendar-day"></i> {{ __($day) }}</h2>
          <div class="nb-day-actions">
            <button type="button" class="btn btn-sm btn-primary" data-add-meal><i class="fas fa-plus"></i> {{ __('Add meal') }}</button>
            <button type="button" class="btn btn-sm btn-light" data-copy-day><i class="fas fa-copy"></i> {{ __('Copy to all days') }}</button>
          </div>
        </div>
        <div class="nb-meals" data-meals>
          @foreach($days[$day] ?? [] as $meal)
            @include('coach._nb-meal', ['mk' => 'm' . $mealIndex++, 'day' => $day, 'meal' => $meal])
          @endforeach
        </div>
        <p class="nb-rest muted small"><i class="fas fa-bed"></i> {{ __('No meals for this day yet (rest day / free day).') }}</p>
        <div class="nb-total nb-day-total" data-day-total></div>
      </section>
    @endforeach
  </div>

  <section class="panel nb-summary">
    <div class="panel-head"><h2><i class="fas fa-chart-pie"></i> {{ __('Week summary') }}</h2></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>{{ __('Day') }}</th><th>{{ __('Calories') }}</th><th>{{ __('Protein (g)') }}</th><th>{{ __('Carbs (g)') }}</th><th>{{ __('Fat (g)') }}</th></tr></thead>
        <tbody data-summary>
          @foreach($weekDays as $day)
            <tr data-summary-day="{{ $day }}"><td>{{ __($day) }}</td><td data-v="calories">0</td><td data-v="protein">0</td><td data-v="carbs">0</td><td data-v="fat">0</td></tr>
          @endforeach
        </tbody>
        <tfoot><tr data-summary-avg><th>{{ __('Daily average') }}</th><th data-v="calories">0</th><th data-v="protein">0</th><th data-v="carbs">0</th><th data-v="fat">0</th></tr></tfoot>
      </table>
    </div>
  </section>

  <div class="form-actions" style="margin-top:16px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> {{ $plan ? __('Save and send the update') : __('Send the plan to my client') }}</button>
  </div>
</form>

<datalist id="food-library">
  @foreach($foods as $food)
    <option value="{{ $food->display_name }}" label="{{ round($food->calories) }} {{ __('kcal') }} / 100 {{ __('g') }}"></option>
  @endforeach
</datalist>

<template id="nb-meal-template">@include('coach._nb-meal', ['mk' => '__M__', 'day' => '__DAY__', 'meal' => []])</template>
<template id="nb-item-template">@include('coach._nb-item', ['mk' => '__M__', 'ik' => '__I__', 'item' => []])</template>
@endsection

@push('scripts')
<script>
  window.NB = {
    foods: @json($foodData),
    i18n: {
      kcal: @js(__('kcal')),
      protein: @js(__('Protein')),
      carbs: @js(__('Carbs')),
      fat: @js(__('Fat')),
      g: @js(__('g')),
      serving: @js(__('serving')),
      mealTotal: @js(__('Meal total')),
      dayTotal: @js(__('Day total')),
      removeMeal: @js(__('Remove this meal and its foods?')),
      copyDay: @js(__('Replace the meals of all other days with the meals of :day?')),
      replaceAll: @js(__('Loading the template replaces the meals you added. Continue?')),
    },
  };
</script>
<script src="{{ asset('js/nutrition-builder.js') }}"></script>
@endpush
