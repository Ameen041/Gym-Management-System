@extends('layouts.admin')
@section('title', 'Templates')
@section('page-title', 'Plan Templates')

@section('content')
<div class="page-header">
  <h1>Templates</h1>
  <p>Manage workout and nutrition templates</p>
</div>

<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
      <h2 style="color: var(--dark); margin: 0;">Plan Templates</h2>
      <p style="color: var(--gray-700); margin-top: 4px;">{{ $templates->count() }} templates available</p>
    </div>
    <a href="{{ route('admin.templates.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i>
      New Template
    </a>
  </div>

  @if($templates->isEmpty())
    <div class="empty-state">
      <i class="fas fa-file-alt"></i>
      <h3>No templates found</h3>
      <p>Create your first workout or nutrition template</p>
      <a href="{{ route('admin.templates.create') }}" class="btn btn-primary" style="margin-top: 16px;">
        <i class="fas fa-plus"></i>
        Create Template
      </a>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Title</th>
            <th>Active</th>
            <th>Updated</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($templates as $tpl)
            <tr>
              <td>{{ $tpl->id }}</td>
              <td>
                @if($tpl->type == 'workout')
                  <span class="badge badge-primary">Workout</span>
                @else
                  <span class="badge badge-success">Nutrition</span>
                @endif
              </td>
              <td>
                <strong>{{ $tpl->title }}</strong>
                @if($tpl->description)
                  <p style="color: var(--gray-700); margin-top: 4px; font-size: 0.9rem;">
                    {{ $tpl->description }}
                  </p>
                @endif
              </td>
              <td>
                @if($tpl->is_active)
                  <span class="badge badge-success">Active</span>
                @else
                  <span class="badge badge-secondary">Inactive</span>
                @endif
              </td>
              <td>{{ $tpl->updated_at->format('Y-m-d') }}</td>
              <td>
                <div style="display: flex; gap: 8px;">
                  <a href="{{ route('admin.templates.edit',$tpl->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-edit"></i>
                    Edit
                  </a>
                  <form action="{{ route('admin.templates.destroy',$tpl->id) }}"
                        method="POST"
                        style="display:inline"
                        onsubmit="return confirm('Delete this template?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                      <i class="fas fa-trash"></i>
                      Delete
                    </button>
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