@extends('layouts.admin')
@section('title', 'Create Template')
@section('page-title', 'Create New Template')

@section('content')
<div class="card">
  @if($errors->any())
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i>
      Please fix the following errors:
      <ul style="margin-top: 8px; margin-left: 20px;">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.templates.store') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="type">Type</label>
      <select name="type" id="type" class="form-control" required>
        <option value="">-- Select type --</option>
        <option value="workout" {{ old('type')=='workout' ? 'selected' : '' }}>Workout</option>
        <option value="nutrition" {{ old('type')=='nutrition' ? 'selected' : '' }}>Nutrition</option>
      </select>
      @error('type')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="title">Title</label>
      <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" required>
      @error('title')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="description">Short Description</label>
      <textarea id="description" name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
      @error('description')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="plan_details">Template Body (plan details)</label>
      <textarea id="plan_details" name="plan_details" class="form-control" rows="8">{{ old('plan_details') }}</textarea>
      @error('plan_details')
        <div class="text-danger">{{ $message }}</div>
      @enderror
      
      <div style="margin-top: 16px; padding: 16px; background: var(--gray-300); border-radius: var(--radius);">
        @php $oldType = old('type'); @endphp
        @if($oldType === 'workout')
          <strong>Workout template format (one exercise per line):</strong><br>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: inline-block; margin: 8px 0;">Day|Muscle|Exercise|Sets|Reps</code><br>
          <p style="margin: 8px 0;">Example:</p>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: block; margin: 4px 0;">Saturday|Chest|Bench Press|4|12</code>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: block; margin: 4px 0;">Saturday|Chest|Incline DB Press|3|10</code>
        @elseif($oldType === 'nutrition')
          <strong>Nutrition template format (one meal per line):</strong><br>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: inline-block; margin: 8px 0;">Day|Meal#|Description|Calories|Protein|Carbs|Fat</code><br>
          <p style="margin: 8px 0;">Example:</p>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: block; margin: 4px 0;">Saturday|1|Oats with milk|350|20|45|10</code>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: block; margin: 4px 0;">Saturday|2|Chicken &amp; Rice|600|40|60|15</code>
        @else
          <strong>Template format:</strong><br>
          <p>For <b>workout</b> templates (one exercise per line):</p>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: inline-block; margin: 4px 0;">Day|Muscle|Exercise|Sets|Reps</code><br>
          <p style="margin: 8px 0;">Example: <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px;">Saturday|Chest|Bench Press|4|12</code></p>
          <p>For <b>nutrition</b> templates (one meal per line):</p>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: inline-block; margin: 4px 0;">Day|Meal#|Description|Calories|Protein|Carbs|Fat</code><br>
          <p style="margin: 8px 0;">Example: <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px;">Saturday|1|Oats with milk|350|20|45|10</code></p>
        @endif
      </div>
    </div>

    <div class="form-group">
      <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
        <span>Active</span>
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <i class="fas fa-save"></i>
        Save Template
      </button>
      <a href="{{ route('admin.templates.index') }}" class="btn btn-secondary">
        <i class="fas fa-times"></i>
        Cancel
      </a>
    </div>
  </form>
</div>
@endsection