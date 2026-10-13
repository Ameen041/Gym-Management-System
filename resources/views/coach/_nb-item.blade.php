{{-- One food line of a meal. $mk = meal key, $ik = item key, $item = values --}}
@php($base = "meals[{$mk}][items][{$ik}]")
<div class="nb-item" data-item>
  <input type="hidden" name="{{ $base }}[food_id]" value="{{ $item['food_id'] ?? '' }}" data-food-id>
  <input type="text" name="{{ $base }}[name]" list="food-library" autocomplete="off" class="nb-food" data-food-name
         value="{{ $item['name'] ?? '' }}" placeholder="{{ __('Food (type to search)') }}" aria-label="{{ __('Food') }}">
  <input type="number" name="{{ $base }}[qty]" step="0.1" min="0" class="nb-qty" data-qty
         value="{{ $item['qty'] ?? '' }}" placeholder="{{ __('Quantity') }}" aria-label="{{ __('Quantity') }}">
  <select name="{{ $base }}[unit]" class="nb-unit" data-unit aria-label="{{ __('Unit') }}">
    <option value="g">{{ __('g') }}</option>
    <option value="serving" data-serving-option @selected(($item['unit'] ?? 'g') === 'serving')>{{ __('serving') }}</option>
  </select>
  @foreach(['calories' => __('kcal'), 'protein' => __('P'), 'carbs' => __('C'), 'fat' => __('F')] as $macro => $short)
    <label class="nb-macro">
      <span>{{ $short }}</span>
      <input type="number" name="{{ $base }}[{{ $macro }}]" step="0.1" min="0" data-macro="{{ $macro }}" value="{{ $item[$macro] ?? '' }}">
    </label>
  @endforeach
  <button type="button" class="nb-icon-btn" data-remove-item title="{{ __('Remove food') }}" aria-label="{{ __('Remove food') }}"><i class="fas fa-times"></i></button>
</div>
