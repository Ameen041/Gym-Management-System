@extends('layouts.admin')
@section('title', __('Templates'))
@section('page-title', __('Plan Templates'))

@section('content')
<div class="page-header">
  <h1>{{ __('Templates') }}</h1>
  <p>{{ __('Manage workout and nutrition templates') }}</p>
</div>

<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
      <h2 style="color: var(--dark); margin: 0;">{{ __('Plan Templates') }}</h2>
      <p style="color: var(--gray-700); margin-top: 4px;">{{ __(':count templates available', ['count' => $templates->count()]) }}</p>
    </div>
    <a href="{{ route('admin.templates.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i>
      {{ __('New Template') }}
    </a>
  </div>

  @if($templates->isEmpty())
    <div class="empty-state">
      <i class="fas fa-file-alt"></i>
      <h3>{{ __('No templates found') }}</h3>
      <p>{{ __('Create your first workout or nutrition template') }}</p>
      <a href="{{ route('admin.templates.create') }}" class="btn btn-primary" style="margin-top: 16px;">
        <i class="fas fa-plus"></i>
        {{ __('Create Template') }}
      </a>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Active') }}</th>
            <th>{{ __('Updated') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($templates as $tpl)
            <tr>
              <td>{{ $tpl->id }}</td>
              <td>
                @if($tpl->type == 'workout')
                  <span class="badge badge-primary">{{ __('Workout') }}</span>
                @else
                  <span class="badge badge-success">{{ __('Nutrition') }}</span>
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
                  <span class="badge badge-success">{{ __('Active') }}</span>
                @else
                  <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                @endif
              </td>
              <td>{{ $tpl->updated_at->format('Y-m-d') }}</td>
              <td>
                <div style="display: flex; gap: 8px;">
                  <a href="{{ route('admin.templates.edit',$tpl->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-edit"></i>
                    {{ __('Edit') }}
                  </a>
                  <form action="{{ route('admin.templates.destroy',$tpl->id) }}"
                        method="POST"
                        style="display:inline"
                        onsubmit="return confirm(@js(__('Delete this template?')))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger">
                      <i class="fas fa-trash"></i>
                      {{ __('Delete') }}
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