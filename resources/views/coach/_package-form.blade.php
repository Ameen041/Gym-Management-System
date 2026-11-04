{{-- Package fields: $package (CoachPackage or null). A price the admin locked can't be changed here. --}}
@php
  $durations = \App\Http\Controllers\Coach\PackageController::DURATIONS;
  $locked = $package?->price_locked ?? false;
  [$min30, $max30] = \App\Models\Setting::packagePriceRange(30);
@endphp
<div class="form-row">
  <div class="form-group">
    <label>{{ __('Package name') }}</label>
    <input type="text" name="title" value="{{ $package->title ?? '' }}" required placeholder="{{ __('e.g. Monthly coaching') }}">
  </div>
  <div class="form-group">
    <label>{{ __('Price (USD)') }}</label>
    <input type="number" name="price" min="0.5" step="0.5" value="{{ $package->price ?? '' }}" required @disabled($locked)>
  </div>
  <div class="form-group">
    <label>{{ __('Duration') }}</label>
    <select name="duration_days" @disabled($locked)>
      @foreach($durations as $days)
        <option value="{{ $days }}" @selected(($package->duration_days ?? 30) == $days)>{{ __(':days days', ['days' => $days]) }}</option>
      @endforeach
    </select>
  </div>
</div>
@if($locked)
  <p class="muted small" style="margin-top:-6px"><i class="fas fa-lock"></i> {{ __('The admin set the price and duration of this package. You can still change the rest.') }}</p>
@else
  <p class="muted small" style="margin-top:-6px">{{ __('Allowed price: :min to :max for 30 days (twice that for 60 days, three times for 90).', ['min' => \App\Support\Money::format($min30), 'max' => \App\Support\Money::format($max30)]) }}</p>
@endif
<div class="form-group">
  <label>{{ __('What is included') }}</label>
  <div class="check-grid">
    <label class="check"><input type="checkbox" name="includes_workout" value="1" @checked($package->includes_workout ?? true)> {{ __('Workout plan') }}</label>
    <label class="check"><input type="checkbox" name="includes_nutrition" value="1" @checked($package->includes_nutrition ?? false)> {{ __('Nutrition plan') }}</label>
    <label class="check">
      <input type="hidden" name="is_active" value="0">
      <input type="checkbox" name="is_active" value="1" @checked($package->is_active ?? true)> {{ __('Visible to trainees') }}
    </label>
  </div>
</div>
<div class="form-group">
  <label>{{ __('Description (optional)') }}</label>
  <textarea name="description" rows="2" placeholder="{{ __('e.g. Weekly plan updates and follow-up in the chat') }}">{{ $package->description ?? '' }}</textarea>
</div>
