@extends('layouts.admin')
@section('title', 'Add User')
@section('page-title', 'Add New User')

@section('content')
<div class="card">
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

  <form method="POST" action="{{ route('admin.users.store') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="name">Name</label>
      <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
      @error('name')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="email">Email</label>
      <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required>
      @error('email')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input type="password" id="password" name="password" class="form-control" required>
      @error('password')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="role">Role</label>
      <select id="role" name="role" class="form-control" required>
        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
        <option value="trainer" {{ old('role') === 'trainer' ? 'selected' : '' }}>Trainer</option>
        <option value="trainee" {{ old('role') === 'trainee' ? 'selected' : '' }}>Trainee</option>
      </select>
      @error('role')
        <div class="text-danger">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">
        <i class="fas fa-user-plus"></i>
        Create User
      </button>
      <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
        <i class="fas fa-times"></i>
        Cancel
      </a>
    </div>
  </form>
</div>
@endsection