@extends('layouts.panel')
@section('title', __('My Packages'))

@section('content')
@php
  use App\Support\Money;
  $commission = \App\Models\Setting::commissionRate();
@endphp

<div class="page-title">
  <h1>{{ __('My Packages') }}</h1>
  <p>{{ __('Set your own prices. The platform keeps :rate% of each subscription.', ['rate' => rtrim(rtrim(number_format($commission, 2), '0'), '.')]) }}</p>
</div>

<div class="grid-2" style="align-items:start">
  <section class="stack">
    @forelse($packages as $package)
      <details class="panel" @if($loop->first && $packages->count() === 1) open @endif>
        <summary style="cursor:pointer;list-style:none">
          <div class="list-row" style="padding:0">
            <div class="grow">
              <strong>{{ $package->title }}</strong>
              @unless($package->is_active)<span class="badge badge-muted">{{ __('Hidden') }}</span>@endunless
              <div class="muted small">{{ $package->includes_label }}</div>
            </div>
            <div style="text-align:end">
              <div class="price">{{ Money::format($package->price) }} <small>/ {{ __(':days days', ['days' => $package->duration_days]) }}</small></div>
              <div class="muted small">{{ __('You receive :amount', ['amount' => Money::format($package->price * (1 - $commission / 100))]) }}</div>
            </div>
          </div>
        </summary>
        <form method="POST" action="{{ route('coach.packages.update', $package) }}" style="margin-top:14px">
          @csrf
          @method('PUT')
          @include('coach._package-form', ['package' => $package])
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('Save') }}</button>
          </div>
        </form>
        <form method="POST" action="{{ route('coach.packages.destroy', $package) }}" style="margin-top:8px" onsubmit="return confirm(@js(__('Delete this package?')))">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-sm btn-light"><i class="fas fa-trash"></i> {{ __('Delete') }}</button>
        </form>
      </details>
    @empty
      <div class="panel empty">
        <i class="fas fa-tags"></i>
        <p>{{ __('You have no packages yet. Add your first package to start getting clients.') }}</p>
      </div>
    @endforelse
  </section>

  <form class="panel" method="POST" action="{{ route('coach.packages.store') }}">
    @csrf
    <div class="panel-head"><h2><i class="fas fa-plus-circle"></i> {{ __('New package') }}</h2></div>
    @include('coach._package-form', ['package' => null])
    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> {{ __('Add package') }}</button>
    <p class="muted small" style="margin-top:10px">{{ __('Tip: in Syria private coaching in gyms costs about $50–100 per month. A lower online price helps you get more clients.') }}</p>
  </form>
</div>
@endsection
