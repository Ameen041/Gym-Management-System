@extends('layouts.admin')
@section('title', __('Foods'))
@section('page-title', __('Food library'))

@section('content')
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
    <div>
      <h2 style="color:var(--dark);margin:0;">{{ __('Food library') }}</h2>
      <p style="color:var(--gray-700);margin-top:4px;">{{ __(':count foods. Values per 100 g, used by coaches to calculate meals.', ['count' => $foods->count()]) }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.foods.index') }}" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="{{ __('Search foods') }}" style="width:180px">
        <select name="category" class="form-control" onchange="this.form.submit()">
          <option value="">{{ __('All categories') }}</option>
          @foreach(\App\Models\Food::CATEGORIES as $key => $label)
            <option value="{{ $key }}" @selected($category === $key)>{{ __($label) }}</option>
          @endforeach
        </select>
      </form>
      <a href="{{ route('admin.foods.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('New food') }}</a>
    </div>
  </div>

  <div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <div>{{ __('The starter values come from the USDA nutrient database (SR28 / FoodData Central "SR Legacy"), a public-domain reference used worldwide. For local products (labneh, akkawi cheese, bakery bread...) add them from the nutrition label on the package.') }}</div>
  </div>

  @if($foods->isEmpty())
    <div class="empty-state"><i class="fas fa-apple-alt"></i><h3>{{ __('No foods found') }}</h3></div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Food') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Calories') }}</th>
            <th>{{ __('Protein (g)') }}</th>
            <th>{{ __('Carbs (g)') }}</th>
            <th>{{ __('Fat (g)') }}</th>
            <th>{{ __('Serving') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($foods as $food)
            <tr>
              <td style="min-width:190px">
                <strong>{{ $food->display_name }}</strong>
                <div style="color:var(--gray-700);font-size:.8rem" dir="ltr">{{ app()->isLocale('ar') ? $food->name : $food->name_ar }}</div>
                @if(!$food->is_active)<span class="badge badge-secondary">{{ __('Hidden') }}</span>@endif
                @if($food->source_url)
                  <a href="{{ $food->source_url }}" target="_blank" rel="noopener" style="font-size:.75rem">USDA #{{ $food->usda_ndb }}</a>
                @endif
              </td>
              <td><span class="badge badge-primary">{{ $food->category_label }}</span></td>
              <td>{{ round($food->calories) }}</td>
              <td>{{ $food->protein + 0 }}</td>
              <td>{{ $food->carbs + 0 }}</td>
              <td>{{ $food->fat + 0 }}</td>
              <td style="font-size:.85rem">@if($food->display_serving){{ $food->display_serving }} = {{ $food->serving_grams + 0 }} {{ __('g') }}@else — @endif</td>
              <td>
                <div style="display:flex;gap:6px;">
                  <a href="{{ route('admin.foods.edit', $food) }}" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i> {{ __('Edit') }}</a>
                  <form action="{{ route('admin.foods.destroy', $food) }}" method="POST" onsubmit="return confirm(@js(__('Delete this food?')))">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
