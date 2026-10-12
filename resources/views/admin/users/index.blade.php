@extends('layouts.admin')
@section('title', __('Users'))
@section('page-title', __('Users Management'))

@section('content')
<div class="page-header">
  <h1>{{ __('Users Management') }}</h1>
  <p>{{ __('Manage all system users') }}</p>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
    <input
        type="text"
        name="name"
        value="{{ request('name') }}"
        placeholder="{{ __('Search by name...') }}"
        class="filter-input"
    >

    <select name="role" class="filter-input">
        <option value="">{{ __('All roles') }}</option>
        <option value="admin"   {{ request('role')=='admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
        <option value="trainer" {{ request('role')=='trainer' ? 'selected' : '' }}>{{ __('Trainer') }}</option>
        <option value="trainee" {{ request('role')=='trainee' ? 'selected' : '' }}>{{ __('Trainee') }}</option>
    </select>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-filter"></i>
        {{ __('Filter') }}
    </button>

    <a href="{{ route('admin.users.index') }}" class="btn btn-light">
        <i class="fas fa-rotate-left"></i>
        {{ __('Reset') }}
    </a>
</form>

<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
      <h2 style="color: var(--dark); margin: 0;">{{ __('All Users') }}</h2>
      <p style="color: var(--gray-700); margin-top: 4px;">{{ __('Total: :count users', ['count' => $users->count()]) }}</p>
    </div>

    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i>
      {{ __('Add New User') }}
    </a>
  </div>

  @if($users->isEmpty())
    <div class="empty-state">
      <i class="fas fa-users"></i>
      <h3>{{ __('No users found') }}</h3>
      <p>{{ __('Start by adding your first user') }}</p>
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary" style="margin-top: 16px;">
        <i class="fas fa-plus"></i>
        {{ __('Add User') }}
      </a>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Role') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Joined') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>

        <tbody>
          @foreach($users as $user)
            <tr>
              <td>#{{ $user->id }}</td>

              <td>
                <div style="display: flex; align-items: center; gap: 12px;">
                  <div style="width: 40px; height: 40px; background: var(--gray-400); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user"></i>
                  </div>
                  <div>
                    <strong>{{ $user->name }}</strong>
                  </div>
                </div>
              </td>

              <td>{{ $user->email }}</td>

              <td>
                @if($user->role === 'admin')
                  <span class="badge badge-primary">{{ __('Admin') }}</span>
                @elseif($user->role === 'trainer')
                  <span class="badge badge-success">{{ __('Trainer') }}</span>
                @else
                  <span class="badge badge-warning">{{ __('Trainee') }}</span>
                @endif
              </td>

              <td>
                @if($user->is_active)
                  <span class="badge badge-success">{{ __('Active') }}</span>
                @else
                  <span class="badge badge-danger">{{ __('Suspended') }}</span>
                @endif
              </td>

              <td>{{ $user->created_at->format('Y-m-d') }}</td>

              <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                  {{-- Edit --}}
                  <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-edit"></i>
                    {{ __('Edit') }}
                  </a>
                 
                  @if($user->role === 'trainee')
                    <a href="{{ route('admin.users.payments', $user->id) }}" class="btn btn-sm btn-primary">
                      <i class="fas fa-credit-card"></i>
                      {{ __('Payments') }}
                    </a>
                  @endif

                  {{-- Activate / Suspend --}}
                  <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button
                      type="submit"
                      class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}"
                      onclick="return confirm(@js($user->is_active ? __('Suspend this user?') : __('Activate this user?')))">
                      <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                      {{ $user->is_active ? __('Suspend') : __('Activate') }}
                    </button>
                  </form>

                  {{-- ✅ Delete --}}
                  <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;"
                        onsubmit="return confirm(@js(__('Are you sure you want to delete this user permanently?')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">
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