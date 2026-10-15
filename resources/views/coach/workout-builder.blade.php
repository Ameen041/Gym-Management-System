@extends('layouts.panel')
@section('title', __('Workout plan') . ' · ' . $subscription->trainee->name)

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\Asset::url('css/builder.css') }}">
@endpush

@section('content')
@php
  use App\Models\Exercise;
  $byId = $library->keyBy('id');

  // Rows per day: from the form after a validation error, otherwise from the saved plan
  if (is_array(old('day'))) {
      $details = [];
      foreach (old('day') as $i => $oldDay) {
          $row = [];
          foreach (['muscle', 'exercise', 'exercise_id', 'sets', 'reps'] as $field) {
              $row[$field] = old($field)[$i] ?? '';
          }
          $details[$oldDay][] = $row;
      }
  }

  $libraryData = $library->map(fn ($e) => [
      'id' => $e->id, 'n' => $e->display_name, 'en' => $e->name, 'ar' => $e->name_ar,
      'm' => $e->muscle_group, 'ml' => Exercise::MUSCLE_GROUPS[$e->muscle_group]['label'] ?? $e->muscle_group,
      'mt' => $e->muscle_label, 'e' => $e->equipment_type, 'et' => $e->display_equipment ?: __('Bodyweight'),
      'v' => $e->has_video, 't' => $e->thumbnail,
  ])->values();
  $muscleCounts = $library->countBy('muscle_group');
  $firstDay = collect($weekDays)->first(fn ($d) => !empty($details[$d])) ?? $weekDays[0];
@endphp

<form method="POST" action="{{ route('coach.plans.save', [$subscription, 'workout']) }}" id="workout-form" class="builder" data-first-day="{{ $firstDay }}">
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
               placeholder="{{ __('Plan name, e.g. Strength - Week 1') }}" aria-label="{{ __('Plan Title') }}">
        <span class="muted small">{{ $subscription->trainee->name }} · {{ $subscription->package_title }}</span>
      </div>
    </div>
    @if($templates->count())
      <select class="builder-template" data-template aria-label="{{ __('Load Workout Template') }}">
        <option value="">{{ __('Template') }}</option>
        @foreach($templates as $tpl)
          <option value="{{ $tpl->id }}" data-body="{{ trim($tpl->plan_details) }}">{{ $tpl->title }}</option>
        @endforeach
      </select>
    @endif
  </div>

  <div class="daytabs" role="tablist">
    @foreach($weekDays as $day)
      <button type="button" class="daytab" role="tab" data-tab="{{ $day }}">
        {{ __(substr($day, 0, 3)) }}<b data-tab-count>0</b>
      </button>
    @endforeach
  </div>

  @foreach($weekDays as $day)
    <section class="builder-day" data-day="{{ $day }}" data-day-label="{{ __($day) }}" role="tabpanel" hidden>
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
      <div class="wb-list" data-list>
        @foreach($details[$day] ?? [] as $row)
          @php($ex = $byId[(int) ($row['exercise_id'] ?? 0)] ?? null)
          @include('coach._wb-row', ['day' => $day, 'row' => $row, 'ex' => $ex])
        @endforeach
      </div>
      <div class="builder-rest"><i class="fas fa-bed"></i> {{ __('Rest day') }}</div>
      <button type="button" class="builder-add" data-open-picker><i class="fas fa-plus"></i> {{ __('Add exercise') }}</button>
    </section>
  @endforeach

  <div class="dock">
    <div class="dock-info"><b data-dock-day></b><span data-dock-week></span></div>
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> {{ $plan ? __('Save') : __('Send') }}</button>
  </div>
</form>

{{-- Exercise picker --}}
<aside class="sheet" id="picker" aria-hidden="true" aria-label="{{ __('Add exercise') }}">
  <div class="sheet-head">
    <h3>{{ __('Add exercise') }} · <span data-picker-day></span></h3>
    <button type="button" class="icon-btn" data-close-picker aria-label="{{ __('Close') }}"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="sheet-tools">
    <div class="search-box">
      <i class="fas fa-magnifying-glass"></i>
      <input type="search" data-search placeholder="{{ __('Search: bench, squat, pull-up…') }}" autocomplete="off" aria-label="{{ __('Search') }}">
    </div>
    <div class="chips" data-muscles>
      <button type="button" class="chip on" data-muscle="">{{ __('All') }}</button>
      @foreach(Exercise::MUSCLE_GROUPS as $key => $group)
        @if($muscleCounts[$key] ?? 0)
          <button type="button" class="chip" data-muscle="{{ $key }}">{{ Exercise::muscleLabel($key) }}</button>
        @endif
      @endforeach
    </div>
    <div class="chips chips-sub" data-equipment>
      @foreach(Exercise::EQUIPMENT_TYPES as $key => $label)
        <button type="button" class="chip" data-eq="{{ $key }}">{{ __($label) }}</button>
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

<template id="wb-row-template">@include('coach._wb-row', ['day' => '__DAY__', 'row' => ['sets' => '', 'reps' => ''], 'ex' => null])</template>
@endsection

@push('scripts')
<script>
  window.WB = {
    library: @json($libraryData),
    muscles: @json(collect(Exercise::MUSCLE_GROUPS)->map(fn ($g, $k) => ['label' => $g['label'], 'text' => Exercise::muscleLabel($k)])),
    defaults: { sets: "3", reps: "10-12", cardioSets: "1", cardioReps: @js(__('20 min')) },
    i18n: {
      exercises: @js(__(':count exercises')),
      oneExercise: @js(__('1 exercise')),
      rest: @js(__('Rest day')),
      week: @js(__(':count exercises in :days days')),
      inDay: @js(__('In this day')),
      noResults: @js(__('No exercise found.')),
      addCustom: @js(__('Add ":name" as your own exercise')),
      video: @js(__('Video')),
      copyConfirm: @js(__('Replace the exercises of :target with the exercises of :day?')),
      allDays: @js(__('all days')),
      templateConfirm: @js(__('Loading the template replaces the exercises you added. Continue?')),
      bodyweight: @js(__('Bodyweight')),
    },
  };
</script>
<script src="{{ \App\Support\Asset::url('js/workout-builder.js') }}"></script>
@endpush
