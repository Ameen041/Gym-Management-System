@extends('layouts.admin')
@section('title', __('Exercises'))
@section('page-title', __('Exercise Library'))

@section('content')
<div class="page-header">
  <h1>{{ __('Exercises') }}</h1>
  <p>{{ __('Weight training exercises with video explanations, shown to trainees and trainers') }}</p>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h2 style="color: var(--dark); margin: 0;">{{ __('Exercise Library') }}</h2>
      <p style="color: var(--gray-700); margin-top: 4px;">{{ __(':count exercises', ['count' => $exercises->count()]) }}</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.exercises.index') }}">
        <select name="muscle" class="form-control" onchange="this.form.submit()">
          <option value="">{{ __('All muscles') }}</option>
          @foreach(\App\Models\Exercise::MUSCLE_GROUPS as $key => $group)
            <option value="{{ $key }}" {{ $muscle === $key ? 'selected' : '' }}>{{ \App\Models\Exercise::muscleLabel($key) }}</option>
          @endforeach
        </select>
      </form>
      <a href="{{ route('exercises.index') }}" class="btn btn-secondary" target="_blank">
        <i class="fas fa-eye"></i> {{ __('View as trainee') }}
      </a>
      <a href="{{ route('admin.exercises.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ __('New Exercise') }}
      </a>
    </div>
  </div>

  @if($exercises->isEmpty())
    <div class="empty-state">
      <i class="fas fa-dumbbell"></i>
      <h3>{{ __('No exercises found') }}</h3>
      <p>{{ __('Add your first exercise with a video explanation') }}</p>
      <a href="{{ route('admin.exercises.create') }}" class="btn btn-primary" style="margin-top: 16px;">
        <i class="fas fa-plus"></i> {{ __('Add Exercise') }}
      </a>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Exercise') }}</th>
            <th>{{ __('Muscle') }}</th>
            <th>{{ __('Level') }}</th>
            <th>{{ __('Video') }}</th>
            <th>{{ __('Visible') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($exercises as $exercise)
            <tr>
              <td>
                <strong>{{ $exercise->display_name }}</strong>
                @if($exercise->display_equipment)
                  <p style="color: var(--gray-700); margin-top: 4px; font-size: 0.9rem;">{{ $exercise->display_equipment }}</p>
                @endif
                @if(!$exercise->name_ar)
                  <span class="badge badge-warning" style="margin-top:4px;">{{ __('No Arabic text') }}</span>
                @endif
              </td>
              <td><span class="badge badge-primary">{{ $exercise->muscle_label }}</span></td>
              <td>{{ $exercise->difficulty_label }}</td>
              <td>
                @if($exercise->has_video)
                  <span class="badge badge-success"><i class="fas fa-video"></i> {{ __('Yes') }}</span>
                @else
                  <span class="badge badge-warning">{{ __('Missing') }}</span>
                @endif
              </td>
              <td>
                @if($exercise->is_active)
                  <span class="badge badge-success">{{ __('Active') }}</span>
                @else
                  <span class="badge badge-secondary">{{ __('Hidden') }}</span>
                @endif
              </td>
              <td>
                <div style="display:flex;gap:8px;">
                  <a href="{{ route('admin.exercises.edit', $exercise->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-edit"></i> {{ __('Edit') }}
                  </a>
                  <form action="{{ route('admin.exercises.destroy', $exercise->id) }}" method="POST" style="display:inline"
                        onsubmit="return confirm(@js(__('Delete this exercise?')))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> {{ __('Delete') }}</button>
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
