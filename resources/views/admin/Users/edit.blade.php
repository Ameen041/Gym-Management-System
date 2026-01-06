@extends('layouts.admin')
@section('title', 'Edit User')
@section('page-title', 'Edit User #' . $user->id)

@section('content')
<div class="card">
  @if(session('status'))
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      {{ session('status') }}
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i>
      Please fix the following errors:
      <ul style="margin-top: 8px; margin-left: 20px;">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
    @csrf
    @method('PUT')

    <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--gray-400);">
      <div style="width: 60px; height: 60px; background: var(--gray-400); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
        <i class="fas fa-user"></i>
      </div>
      <div>
        <h3 style="color: var(--dark); margin: 0;">{{ $user->name }}</h3>
        <p style="color: var(--gray-700); margin: 4px 0 0 0;">ID: #{{ $user->id }} • {{ ucfirst($user->role) }} • {{ $user->is_active ? 'Active' : 'Suspended' }}</p>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label" for="name">Name</label>
      <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
      @error('name')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="email">Email</label>
      <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
      @error('email')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="role">Role</label>
      <select id="role" name="role" class="form-control" required>
        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
        <option value="trainer" {{ old('role', $user->role) === 'trainer' ? 'selected' : '' }}>Trainer</option>
        <option value="trainee" {{ old('role', $user->role) === 'trainee' ? 'selected' : '' }}>Trainee</option>
      </select>
      @error('role')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="password">New Password (optional)</label>
      <input type="password" id="password" name="password" class="form-control" placeholder="Leave empty to keep current password">
      @error('password')
        <div class="text-danger">{{ $message }}</div>
      @enderror
      <p style="color: var(--gray-700); font-size: 0.875rem; margin-top: 4px;">
        <i class="fas fa-info-circle"></i> Only enter if you want to change the password
      </p>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <i class="fas fa-save"></i>
        Save Changes
      </button>
      <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i>
        Back to Users
      </a>
    </div>
  </form>
</div>
@endsection