{{-- One food of a meal. $mk = meal key, $ik = item key, $item = values.
     Library foods show their name and calculated values; own foods show inputs. --}}
@php($base = "meals[{$mk}][items][{$ik}]")
<div class="nb-item" data-item>
  <input type="hidden" name="{{ $base }}[food_id]" value="{{ $item['food_id'] ?? '' }}" data-food-id>
  <div class="nb-food">
    <input type="text" name="{{ $base }}[name]" value="{{ $item['name'] ?? '' }}" class="nb-name-input" data-food-name
           maxlength="255" placeholder="{{ __('Food name') }}" aria-label="{{ __('Food name') }}">
    <strong class="nb-food-name" data-food-label></strong>
    <span class="nb-food-meta" data-food-meta></span>
  </div>
  <div class="nb-amount">
    <input type="number" name="{{ $base }}[qty]" step="0.1" min="0" value="{{ $item['qty'] ?? '' }}" data-qty
           placeholder="{{ __('Qty') }}" aria-label="{{ __('Quantity') }}">
    <select name="{{ $base }}[unit]" data-unit aria-label="{{ __('Unit') }}">
      <option value="g">{{ __('g') }}</option>
      <option value="serving" data-serving-option @selected(($item['unit'] ?? 'g') === 'serving')>{{ __('serving') }}</option>
    </select>
  </div>
  <div class="nb-macro-inputs">
    @foreach(['calories' => __('kcal'), 'protein' => __('P'), 'carbs' => __('C'), 'fat' => __('F')] as $macro => $short)
      <label><span>{{ $short }}</span><input type="number" name="{{ $base }}[{{ $macro }}]" step="0.1" min="0" data-macro="{{ $macro }}" value="{{ $item[$macro] ?? '' }}"></label>
    @endforeach
  </div>
  <div class="nb-item-sum" data-item-sum></div>
  <button type="button" class="wb-act danger" data-remove-item title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}"><i class="fas fa-xmark"></i></button>
</div>
