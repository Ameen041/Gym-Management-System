{{-- One meal of a day. $mk = meal key, $day, $meal = ['number','note','items'] --}}
<div class="nb-meal" data-meal="{{ $mk }}">
  <input type="hidden" name="meals[{{ $mk }}][day]" value="{{ $day }}" data-day-input>
  <div class="nb-meal-head">
    <label class="nb-meal-no">
      <span>{{ __('Meal') }}</span>
      <input type="number" name="meals[{{ $mk }}][number]" min="1" max="20" value="{{ $meal['number'] ?? '' }}" data-meal-number>
    </label>
    <input type="text" name="meals[{{ $mk }}][note]" class="nb-note" value="{{ $meal['note'] ?? '' }}" maxlength="255"
           placeholder="{{ __('Name or note (optional): breakfast, after training...') }}">
    <button type="button" class="nb-icon-btn danger" data-remove-meal title="{{ __('Remove meal') }}" aria-label="{{ __('Remove meal') }}"><i class="fas fa-trash-alt"></i></button>
  </div>
  <div class="nb-items" data-items>
    @foreach(($meal['items'] ?? []) ?: [[]] as $item)
      @include('coach._nb-item', ['mk' => $mk, 'ik' => 'i' . $loop->index, 'item' => $item])
    @endforeach
  </div>
  <div class="nb-meal-foot">
    <button type="button" class="btn btn-sm" data-add-item><i class="fas fa-plus"></i> {{ __('Add food') }}</button>
    <div class="nb-total" data-meal-total></div>
  </div>
</div>
