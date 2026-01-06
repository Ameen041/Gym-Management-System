@extends('layouts.admin')
@section('title', 'Edit Template')
@section('page-title', 'Edit Template')

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

  <form method="POST" action="{{ route('admin.templates.update',$template->id) }}">
    @csrf
    @method('PUT')

    <div class="form-group">
      <label class="form-label">Type</label>
      <select name="type" class="form-control" required>
        <option value="workout" {{ old('type',$template->type)=='workout'?'selected':'' }}>Workout</option>
        <option value="nutrition" {{ old('type',$template->type)=='nutrition'?'selected':'' }}>Nutrition</option>
      </select>
      @error('type')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label">Title</label>
      <input type="text" name="title" class="form-control" value="{{ old('title',$template->title) }}" required>
      @error('title')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label">Short Description</label>
      <textarea name="description" class="form-control" rows="2">{{ old('description',$template->description) }}</textarea>
      @error('description')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label">Template Body (plan details)</label>
      <textarea name="plan_details" class="form-control" rows="8">{{ old('plan_details',$template->plan_details) }}</textarea>
      @error('plan_details')
        <div class="text-danger">{{ $message }}</div>
      @enderror
      
      <div style="margin-top: 16px; padding: 16px; background: var(--gray-300); border-radius: var(--radius);">
        @php $type = old('type',$template->type); @endphp
        @if($type === 'workout')
          <strong>Workout template format (one exercise per line):</strong><br>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: inline-block; margin: 8px 0;">Day|Muscle|Exercise|Sets|Reps</code><br>
          <p style="margin: 8px 0;">Example:</p>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: block; margin: 4px 0;">Saturday|Chest|Bench Press|4|12</code>
          <code style="background: var(--gray-800); color: var(--white); padding: 4px 8px; border-radius: 4px; display: block; margin: 4px 0;">Saturday|Chest|Incline DB Press|3|10</code>
        @elseif($type === 'nutrition')
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
        <input type="checkbox" name="is_active" value="1" {{ old('is_active',$template->is_active) ? 'checked' : '' }}>
        <span>Active</span>
      </label>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <i class="fas fa-save"></i>
        Update Template
      </button>
      <a href="{{ route('admin.templates.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i>
        Back to Templates
      </a>
    </div>
  </form>
</div>
@endsection