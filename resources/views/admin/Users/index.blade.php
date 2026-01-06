@extends('layouts.admin')
@section('title', 'Users')
@section('page-title', 'Users Management')

@section('content')
<div class="page-header">
  <h1>Users Management</h1>
  <p>Manage all system users</p>
</div>

<form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
    <input
        type="text"
        name="name"
        value="{{ request('name') }}"
        placeholder="Search by name..."
        class="filter-input"
    >

    <select name="role" class="filter-input">
        <option value="">All roles</option>
        <option value="admin"   {{ request('role')=='admin' ? 'selected' : '' }}>Admin</option>
        <option value="trainer" {{ request('role')=='trainer' ? 'selected' : '' }}>Trainer</option>
        <option value="trainee" {{ request('role')=='trainee' ? 'selected' : '' }}>Trainee</option>
    </select>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-filter"></i>
        Filter
    </button>

    <a href="{{ route('admin.users.index') }}" class="btn btn-light">
        <i class="fas fa-rotate-left"></i>
        Reset
    </a>
</form>

<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
      <h2 style="color: var(--dark); margin: 0;">All Users</h2>
      <p style="color: var(--gray-700); margin-top: 4px;">Total: {{ $users->count() }} users</p>
    </div>

    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i>
      Add New User
    </a>
  </div>

  @if($users->isEmpty())
    <div class="empty-state">
      <i class="fas fa-users"></i>
      <h3>No users found</h3>
      <p>Start by adding your first user</p>
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary" style="margin-top: 16px;">
        <i class="fas fa-plus"></i>
        Add User
      </a>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Joined</th>
            <th>Actions</th>
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
                  <span class="badge badge-primary">Admin</span>
                @elseif($user->role === 'trainer')
                  <span class="badge badge-success">Trainer</span>
                @else
                  <span class="badge badge-warning">Trainee</span>
                @endif
              </td>

              <td>
                @if($user->is_active)
                  <span class="badge badge-success">Active</span>
                @else
                  <span class="badge badge-danger">Suspended</span>
                @endif
              </td>

              <td>{{ $user->created_at->format('Y-m-d') }}</td>

              <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                  {{-- Edit --}}
                  <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-edit"></i>
                    Edit
                  </a>
                 
                  @if($user->role === 'trainee')
                    <a href="{{ route('admin.users.payments', $user->id) }}" class="btn btn-sm btn-primary">
                      <i class="fas fa-credit-card"></i>
                      Payments
                    </a>
                  @endif

                  {{-- Activate / Suspend --}}
                  <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button
                      type="submit"
                      class="btn btn-sm {{ $user->is_active ? 'btn-danger' : 'btn-success' }}"
                      onclick="return confirm('{{ $user->is_active ? 'Suspend' : 'Activate' }} this user?')">
                      <i class="fas {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                      {{ $user->is_active ? 'Suspend' : 'Activate' }}
                    </button>
                  </form>

                  {{-- ✅ Delete --}}
                  <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;"
                        onsubmit="return confirm('Are you sure you want to delete this user permanently?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">
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