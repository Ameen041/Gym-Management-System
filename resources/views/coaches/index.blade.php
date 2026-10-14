@extends('layouts.panel')
@section('title', __('Find your coach'))

@section('content')
@php use App\Models\CoachProfile; use App\Support\Money; @endphp

<div class="page-title">
  <h1>{{ __('Find your coach') }}</h1>
  <p>{{ __('Certified online coaches build your workout and nutrition plan and follow up with you on WhatsApp.') }}</p>
</div>

<form class="panel filters" method="GET" action="{{ route('coaches.index') }}">
  <div class="form-group" style="margin:0">
    <label>{{ __('Search') }}</label>
    <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Coach name') }}">
  </div>
  <div class="form-group" style="margin:0">
    <label>{{ __('Specialty') }}</label>
    <select name="specialty">
      <option value="">{{ __('All') }}</option>
      @foreach(CoachProfile::SPECIALTIES as $key => $label)
        <option value="{{ $key }}" @selected($filters['specialty'] === $key)>{{ __($label) }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group" style="margin:0">
    <label>{{ __('City') }}</label>
    <select name="city">
      <option value="">{{ __('All') }}</option>
      @foreach(CoachProfile::CITIES as $key => $label)
        <option value="{{ $key }}" @selected($filters['city'] === $key)>{{ __($label) }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group" style="margin:0">
    <label>{{ __('Coach gender') }}</label>
    <select name="gender">
      <option value="">{{ __('All') }}</option>
      <option value="male" @selected($filters['gender'] === 'male')>{{ __('Male') }}</option>
      <option value="female" @selected($filters['gender'] === 'female')>{{ __('Female') }}</option>
    </select>
  </div>
  <div class="form-group" style="margin:0">
    <label>{{ __('Max price / month') }}</label>
    <select name="max_price">
      <option value="">{{ __('Any') }}</option>
      @foreach([20, 30, 40, 50, 75, 100] as $max)
        <option value="{{ $max }}" @selected((string) $filters['max_price'] === (string) $max)>{{ Money::format($max) }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-group" style="margin:0">
    <label>{{ __('Sort by') }}</label>
    <select name="sort">
      @foreach(\App\Http\Controllers\CoachDirectoryController::SORTS as $key => $label)
        <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ __($label) }}</option>
      @endforeach
    </select>
  </div>
  <div class="form-actions" style="margin:0">
    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> {{ __('Filter') }}</button>
    <a href="{{ route('coaches.index') }}" class="btn btn-light">{{ __('Reset') }}</a>
  </div>
</form>

<p class="muted small" style="margin:14px 2px">{{ __(':count coaches', ['count' => $coaches->count()]) }}</p>

@if($coaches->isEmpty())
  <div class="panel empty">
    <i class="fas fa-user-slash"></i>
    <h3>{{ __('No coaches match your search') }}</h3>
    <p>{{ __('Try removing some filters.') }}</p>
  </div>
@else
  <div class="grid-3">
    @foreach($coaches as $coach)
      @php($profile = $coach->coachProfile)
      @include('partials.coach-card', ['coach' => $coach])
    @endforeach
  </div>
@endif
@endsection
