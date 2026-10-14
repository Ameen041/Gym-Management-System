@extends('layouts.panel')
@section('title', __('Find your coach'))

@section('content')
@php use App\Models\CoachProfile; use App\Support\Money; @endphp

@php($more = collect(['city', 'gender', 'max_price'])->filter(fn ($k) => filled($filters[$k]))->count() + ($filters['sort'] !== array_key_first(\App\Http\Controllers\CoachDirectoryController::SORTS) ? 1 : 0))
<form class="coach-filter" method="GET" action="{{ route('coaches.index') }}">
  <div class="search-box">
    <i class="fas fa-magnifying-glass"></i>
    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('Search by coach name') }}" aria-label="{{ __('Search') }}">
  </div>
  {{-- the clicked chip comes after this field in the form, so it wins --}}
  @if($filters['specialty'])<input type="hidden" name="specialty" value="{{ $filters['specialty'] }}">@endif
  <div class="chips">
    <button type="submit" name="specialty" value="" class="chip {{ $filters['specialty'] ? '' : 'on' }}">{{ __('All') }}</button>
    @foreach(CoachProfile::SPECIALTIES as $key => $label)
      <button type="submit" name="specialty" value="{{ $key }}" class="chip {{ $filters['specialty'] === $key ? 'on' : '' }}">{{ __($label) }}</button>
    @endforeach
  </div>

  <details class="panel fold more-filters" @if($more) open @endif>
    <summary><span><i class="fas fa-sliders"></i> {{ __('More filters') }} @if($more)<b class="badge">{{ $more }}</b>@endif</span><i class="fas fa-chevron-down fold-icon"></i></summary>
    <div class="filters" style="margin-top:12px">
      <div class="form-group" style="margin:0">
        <label>{{ __('City') }}</label>
        <select name="city" onchange="this.form.submit()">
          <option value="">{{ __('All') }}</option>
          @foreach(CoachProfile::CITIES as $key => $label)
            <option value="{{ $key }}" @selected($filters['city'] === $key)>{{ __($label) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>{{ __('Coach gender') }}</label>
        <select name="gender" onchange="this.form.submit()">
          <option value="">{{ __('All') }}</option>
          <option value="male" @selected($filters['gender'] === 'male')>{{ __('Male') }}</option>
          <option value="female" @selected($filters['gender'] === 'female')>{{ __('Female') }}</option>
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>{{ __('Max price / month') }}</label>
        <select name="max_price" onchange="this.form.submit()">
          <option value="">{{ __('Any') }}</option>
          @foreach([20, 30, 40, 50, 75, 100] as $max)
            <option value="{{ $max }}" @selected((string) $filters['max_price'] === (string) $max)>{{ Money::format($max) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin:0">
        <label>{{ __('Sort by') }}</label>
        <select name="sort" onchange="this.form.submit()">
          @foreach(\App\Http\Controllers\CoachDirectoryController::SORTS as $key => $label)
            <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ __($label) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-actions" style="margin:0">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> {{ __('Filter') }}</button>
        <a href="{{ route('coaches.index') }}" class="btn btn-light">{{ __('Reset') }}</a>
      </div>
    </div>
  </details>
</form>

<p class="muted small" style="margin:12px 2px">{{ __(':count coaches', ['count' => $coaches->count()]) }}</p>

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
