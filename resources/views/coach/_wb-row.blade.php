{{-- One exercise of a day in the workout builder.
     $day, $row = [muscle, exercise, exercise_id, sets, reps], $ex = library Exercise or null --}}
@php
  use App\Models\Exercise;
  $muscleLabel = $ex ? (Exercise::MUSCLE_GROUPS[$ex->muscle_group]['label'] ?? $ex->muscle_group) : ($row['muscle'] ?? '');
@endphp
<div class="wb-row {{ $ex ? '' : 'is-custom' }}" data-row>
  <input type="hidden" name="day[]" value="{{ $day }}" data-day-input>
  <input type="hidden" name="exercise_id[]" value="{{ $ex?->id }}" data-ex-id>
  <span class="wb-order" data-order></span>
  <span class="pick-icon">
    @if($ex?->thumbnail)<img src="{{ $ex->thumbnail }}" alt="" loading="lazy">@else<i class="fas fa-dumbbell"></i>@endif
  </span>
  <div class="wb-info">
    @if($ex)
      <input type="hidden" name="exercise[]" value="{{ $ex->display_name }}">
      <input type="hidden" name="muscle[]" value="{{ $muscleLabel }}" data-muscle>
      <strong class="wb-name">{{ $ex->display_name }}</strong>
      <span class="wb-meta">{{ $ex->muscle_label }} · {{ $ex->display_equipment ?: __('Bodyweight') }}@if($ex->has_video) · <i class="fas fa-circle-play" title="{{ __('Video') }}"></i>@endif</span>
    @else
      <input type="text" name="exercise[]" value="{{ $row['exercise'] ?? '' }}" class="wb-name-input" placeholder="{{ __('Exercise name') }}" aria-label="{{ __('Exercise name') }}">
      <select name="muscle[]" class="wb-muscle" data-muscle aria-label="{{ __('Muscle') }}">
        <option value="">{{ __('Muscle') }}</option>
        @foreach(Exercise::MUSCLE_GROUPS as $key => $group)
          <option value="{{ $group['label'] }}" @selected(strcasecmp($muscleLabel, $group['label']) === 0)>{{ Exercise::muscleLabel($key) }}</option>
        @endforeach
      </select>
    @endif
  </div>
  <div class="wb-sr">
    <label><span>{{ __('Sets') }}</span><input type="text" name="sets[]" inputmode="numeric" value="{{ $row['sets'] ?? '' }}" maxlength="10"></label>
    <span class="wb-x">×</span>
    <label><span>{{ __('Reps') }}</span><input type="text" name="reps[]" value="{{ $row['reps'] ?? '' }}" maxlength="20"></label>
  </div>
  <div class="wb-actions">
    <button type="button" class="wb-act" data-move="-1" title="{{ __('Move up') }}" aria-label="{{ __('Move up') }}"><i class="fas fa-arrow-up"></i></button>
    <button type="button" class="wb-act" data-move="1" title="{{ __('Move down') }}" aria-label="{{ __('Move down') }}"><i class="fas fa-arrow-down"></i></button>
    <button type="button" class="wb-act danger" data-remove title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}"><i class="fas fa-xmark"></i></button>
  </div>
</div>
