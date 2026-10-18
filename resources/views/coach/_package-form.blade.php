{{-- Package fields: $package (CoachPackage or null) --}}
@php($durations = \App\Http\Controllers\Coach\PackageController::DURATIONS)
<div class="form-row">
  <div class="form-group">
    <label>{{ __('Package name') }}</label>
    <input type="text" name="title" value="{{ $package->title ?? '' }}" required placeholder="{{ __('e.g. Monthly coaching') }}">
  </div>
  <div class="form-group">
    <label>{{ __('Price (USD)') }}</label>
    <input type="number" name="price" min="5" max="1000" step="0.5" value="{{ $package->price ?? '' }}" required>
  </div>
  <div class="form-group">
    <label>{{ __('Duration') }}</label>
    <select name="duration_days">
      @foreach($durations as $days)
        <option value="{{ $days }}" @selected(($package->duration_days ?? 30) == $days)>{{ __(':days days', ['days' => $days]) }}</option>
      @endforeach
    </select>
  </div>
</div>
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
