@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-users"></i>
    </div>
    <div class="stat-value">{{ $stats['users'] }}</div>
    <div class="stat-label">Total Users</div>
  </div>


  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-user-tie"></i>
    </div>
    <div class="stat-value">{{ $stats['trainers'] }}</div>
    <div class="stat-label">Trainers</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-user-graduate"></i>
    </div>
    <div class="stat-value">{{ $stats['trainees'] }}</div>
    <div class="stat-label">Trainees</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <i class="fas fa-clock"></i>
    </div>
    <div class="stat-value">{{ $stats['pending_requests'] }}</div>
    <div class="stat-label">Pending Requests</div>
  </div>
</div>

<div class="card">
  <h3 style="margin-bottom: 20px; color: var(--dark);">Quick Actions</h3>
  <div style="display: flex; gap: 16px; flex-wrap: wrap;">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
      <i class="fas fa-user-plus"></i>
      Add New User
    </a>
    <a href="{{ route('admin.templates.create') }}" class="btn btn-success">
      <i class="fas fa-file-alt"></i>
      Create Template
    </a>
    <a href="{{ route('admin.requests.index') }}" class="btn btn-warning">
      <i class="fas fa-envelope"></i>
      View Requests
    </a>
    <a href="{{ route('admin.subscriptions.expired') }}" class="btn btn-danger">
      <i class="fas fa-clock"></i>
      Check Expired
    </a>
  </div>
</div>
@endsection