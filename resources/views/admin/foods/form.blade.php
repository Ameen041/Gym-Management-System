@extends('layouts.admin')
@section('title', $food->exists ? __('Edit food') : __('New food'))
@section('page-title', $food->exists ? __('Edit food') : __('New food'))

@section('content')
<div class="card">
  @if($errors->any())
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i>
      <ul style="margin-inline-start:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ $food->exists ? route('admin.foods.update', $food) : route('admin.foods.store') }}">
    @csrf
    @if($food->exists) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
      <div class="form-group">
        <label class="form-label">{{ __('Name (English)') }}</label>
        <input type="text" name="name" class="form-control" dir="ltr" value="{{ old('name', $food->name) }}" required>
      </div>
      <div class="form-group">
        <label class="form-label">{{ __('Name (Arabic)') }}</label>
        <input type="text" name="name_ar" class="form-control" dir="rtl" value="{{ old('name_ar', $food->name_ar) }}">
      </div>
      <div class="form-group">
        <label class="form-label">{{ __('Category') }}</label>
        <select name="category" class="form-control" required>
          @foreach(\App\Models\Food::CATEGORIES as $key => $label)
            <option value="{{ $key }}" @selected(old('category', $food->category) === $key)>{{ __($label) }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <h3 style="margin:10px 0 12px">{{ __('Nutrition values per 100 g') }}</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;">
      @foreach(['calories' => __('Calories (kcal)'), 'protein' => __('Protein (g)'), 'carbs' => __('Carbs (g)'), 'fat' => __('Fat (g)'), 'fiber' => __('Fiber (g)')] as $field => $label)
        <div class="form-group">
          <label class="form-label">{{ $label }}</label>
          <input type="number" step="0.01" min="0" name="{{ $field }}" class="form-control" value="{{ old($field, $food->{$field}) }}" {{ $field === 'fiber' ? '' : 'required' }}>
        </div>
      @endforeach
    </div>

    <h3 style="margin:10px 0 4px">{{ __('Common serving (optional)') }}</h3>
    <p style="color:var(--gray-700);margin-bottom:12px;font-size:.9rem">{{ __('Lets coaches enter "2 eggs" or "1 loaf" instead of grams.') }}</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
      <div class="form-group">
        <label class="form-label">{{ __('Serving weight (g)') }}</label>
        <input type="number" step="0.1" min="0" name="serving_grams" class="form-control" value="{{ old('serving_grams', $food->serving_grams) }}" placeholder="50">
      </div>
      <div class="form-group">
        <label class="form-label">{{ __('Serving name (English)') }}</label>
        <input type="text" name="serving_name" class="form-control" dir="ltr" value="{{ old('serving_name', $food->serving_name) }}" placeholder="1 large egg">
      </div>
      <div class="form-group">
        <label class="form-label">{{ __('Serving name (Arabic)') }}</label>
        <input type="text" name="serving_name_ar" class="form-control" dir="rtl" value="{{ old('serving_name_ar', $food->serving_name_ar) }}" placeholder="بيضة كبيرة">
      </div>
    </div>

    <div class="form-group">
      <label style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $food->is_active))>
        {{ __('Visible to coaches') }}
      </label>
    </div>

    <div class="form-actions">
      <a href="{{ route('admin.foods.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('Save') }}</button>
    </div>
  </form>
</div>
@endsection
