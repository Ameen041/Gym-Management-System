@extends('layouts.admin')
@section('title', __('Packages'))
@section('page-title', __('Coach packages'))

@section('content')
@php
  use App\Support\Money;
  [$min30, $max30] = \App\Models\Setting::packagePriceRange(30);
@endphp
<div class="page-header">
  <h1>{{ __('Coach packages') }}</h1>
  <p>{{ __('Change a price, hide a package, or lock its price so the coach cannot change it. New subscriptions and renewals use the new price; subscriptions already created keep theirs.') }}</p>
</div>

<div class="card">
  <div class="alert" style="margin-bottom:16px">
    <i class="fas fa-sliders"></i>
    <div>
      {{ __('Coaches can choose a price from :min to :max for 30 days (twice that for 60 days, three times for 90).', ['min' => Money::format($min30), 'max' => Money::format($max30)]) }}
      <a href="{{ route('admin.settings.edit') }}#package-prices">{{ __('Change the range') }}</a>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
  @endif

  <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px">
    <div class="tabs">
      <a href="{{ route('admin.packages.index', array_filter(['q' => $search, 'coach' => $coach?->id])) }}" class="tab {{ $outOfRange ? '' : 'active' }}">{{ __('All') }}</a>
      <a href="{{ route('admin.packages.index', array_filter(['q' => $search, 'coach' => $coach?->id, 'out' => 1])) }}" class="tab {{ $outOfRange ? 'active' : '' }}">
        {{ __('Outside the range') }} ({{ $outCount }})
      </a>
    </div>
    <form method="GET" class="filter-bar" style="margin:0">
      @if($outOfRange)<input type="hidden" name="out" value="1">@endif
      <input type="text" name="q" value="{{ $search }}" class="filter-input" placeholder="{{ __('Coach or package name') }}">
      <button class="btn btn-primary">{{ __('Search') }}</button>
    </form>
  </div>

  @if($coach)
    <p style="margin-bottom:12px">
      {{ __('Packages of :name', ['name' => $coach->name]) }} ·
      <a href="{{ route('admin.packages.index', array_filter(['out' => $outOfRange ? 1 : null])) }}">{{ __('All coaches') }}</a>
    </p>
  @endif

  @if($packages->isEmpty())
    <div class="empty-state"><i class="fas fa-tags"></i><h3>{{ __('Nothing here') }}</h3></div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Coach') }}</th>
            <th>{{ __('Package') }}</th>
            <th>{{ __('Price (USD)') }}</th>
            <th>{{ __('Visible to trainees') }}</th>
            <th>{{ __('Lock the price') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($packages as $package)
            @php([$min, $max] = $package->price_range)
            <tr>
              <td><a href="{{ route('admin.coaches.show', $package->coach_id) }}"><strong>{{ $package->coach->name ?? '—' }}</strong></a></td>
              <td>
                {{ $package->title }}
                <div style="color:var(--gray-700);font-size:.8rem">{{ __(':days days', ['days' => $package->duration_days]) }} · {{ $package->includes_label }}</div>
              </td>
              <td>
                <input type="number" name="price" form="package-{{ $package->id }}" value="{{ $package->price + 0 }}" min="1" max="10000" step="0.5"
                       class="form-control" style="max-width:120px" required aria-label="{{ __('Price (USD)') }}">
                @if($package->duration_days != 30)
                  <div style="color:var(--gray-700);font-size:.8rem">{{ __(':price for 30 days', ['price' => Money::format($package->monthly_price)]) }}</div>
                @endif
                @unless($package->in_range)
                  <span class="badge badge-warning" title="{{ Money::format($min) }} – {{ Money::format($max) }}"><i class="fas fa-triangle-exclamation"></i> {{ __('Outside the range') }}</span>
                @endunless
              </td>
              <td>
                <input type="hidden" name="is_active" value="0" form="package-{{ $package->id }}">
                <label><input type="checkbox" name="is_active" value="1" form="package-{{ $package->id }}" @checked($package->is_active)> {{ __('Visible') }}</label>
              </td>
              <td>
                <input type="hidden" name="price_locked" value="0" form="package-{{ $package->id }}">
                <label><input type="checkbox" name="price_locked" value="1" form="package-{{ $package->id }}" @checked($package->price_locked)> <i class="fas fa-lock"></i> {{ __('Locked') }}</label>
              </td>
              <td>
                <form id="package-{{ $package->id }}" method="POST" action="{{ route('admin.packages.update', $package) }}">
                  @csrf
                  @method('PUT')
                  <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('Save') }}</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $packages->links('partials.pager') }}
  @endif
</div>
@endsection
