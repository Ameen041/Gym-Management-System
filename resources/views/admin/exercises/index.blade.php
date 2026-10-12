@extends('layouts.admin')
@section('title', 'Exercises')
@section('page-title', 'Exercise Library')

@section('content')
<div class="page-header">
  <h1>Exercises</h1>
  <p>Weight training exercises with video explanations, shown to trainees and trainers</p>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px;">
    <div>
      <h2 style="color: var(--dark); margin: 0;">Exercise Library</h2>
      <p style="color: var(--gray-700); margin-top: 4px;">{{ $exercises->count() }} exercises</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <form method="GET" action="{{ route('admin.exercises.index') }}">
        <select name="muscle" class="form-control" onchange="this.form.submit()">
          <option value="">All muscles</option>
          @foreach(\App\Models\Exercise::MUSCLE_GROUPS as $key => $group)
            <option value="{{ $key }}" {{ $muscle === $key ? 'selected' : '' }}>{{ $group['label'] }}</option>
          @endforeach
        </select>
      </form>
      <a href="{{ route('exercises.index') }}" class="btn btn-secondary" target="_blank">
        <i class="fas fa-eye"></i> View as trainee
      </a>
      <a href="{{ route('admin.exercises.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Exercise
      </a>
    </div>
  </div>

  @if($exercises->isEmpty())
    <div class="empty-state">
      <i class="fas fa-dumbbell"></i>
      <h3>No exercises found</h3>
      <p>Add your first exercise with a video explanation</p>
      <a href="{{ route('admin.exercises.create') }}" class="btn btn-primary" style="margin-top: 16px;">
        <i class="fas fa-plus"></i> Add Exercise
      </a>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Exercise</th>
            <th>Muscle</th>
            <th>Level</th>
            <th>Video</th>
            <th>Visible</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($exercises as $exercise)
            <tr>
              <td>
                <strong>{{ $exercise->name }}</strong>
                @if($exercise->equipment)
                  <p style="color: var(--gray-700); margin-top: 4px; font-size: 0.9rem;">{{ $exercise->equipment }}</p>
                @endif
              </td>
              <td><span class="badge badge-primary">{{ $exercise->muscle_label }}</span></td>
              <td>{{ \App\Models\Exercise::DIFFICULTIES[$exercise->difficulty] ?? $exercise->difficulty }}</td>
              <td>
                @if($exercise->has_video)
                  <span class="badge badge-success"><i class="fas fa-video"></i> Yes</span>
                @else
                  <span class="badge badge-warning">Missing</span>
                @endif
              </td>
              <td>
                @if($exercise->is_active)
                  <span class="badge badge-success">Active</span>
                @else
                  <span class="badge badge-secondary">Hidden</span>
                @endif
              </td>
              <td>
                <div style="display:flex;gap:8px;">
                  <a href="{{ route('admin.exercises.edit', $exercise->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <form action="{{ route('admin.exercises.destroy', $exercise->id) }}" method="POST" style="display:inline"
                        onsubmit="return confirm('Delete this exercise?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
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
