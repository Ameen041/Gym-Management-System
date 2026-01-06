@extends('layouts.admin')
@section('title', 'Expired Subscriptions')
@section('page-title', 'Expired Subscriptions')

@section('content')
<div class="page-header">
  <h1>Expired Subscriptions</h1>
  <p>Manage expired user subscriptions</p>
</div>

<div class="card">
  <div style="margin-bottom: 24px;">
    <h2 style="color: var(--dark); margin-bottom: 12px;">Expired Subscriptions</h2>
    
    <form method="GET" class="filter-bar">
      <input type="text" name="name" class="filter-input" placeholder="Search by trainee name..." value="{{ request('name') }}">
      <button class="btn btn-primary" type="submit">Search</button>
      
      @if(request('name'))
        <a href="{{ route('admin.subscriptions.expired') }}" class="btn btn-secondary">
          Reset
        </a>
      @endif
    </form>
  </div>

  @if($expiredTrainees->isEmpty())
    <div class="empty-state">
      <i class="fas fa-check-circle" style="color: var(--success);"></i>
      <h3>All subscriptions are active</h3>
      <p>No expired subscriptions found. Great job!</p>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Trainee</th>
            <th>Email</th>
            <th style="width: 180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($expiredTrainees as $u)
            <tr>
              <td>{{ $u->id }}</td>
              <td>
                <div style="display: flex; align-items: center; gap: 12px;">
                  <div style="width: 40px; height: 40px; background: var(--gray-400); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user"></i>
                  </div>
                  <div>
                    <strong>{{ $u->name }}</strong>
                  </div>
                </div>
              </td>
              <td>{{ $u->email }}</td>
              <td>
                <div style="display: flex; gap: 8px;">
                  <a href="{{ route('admin.users.payments', $u->id) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-credit-card"></i>
                    Add Payment
                  </a>
                  <form action="{{ route('admin.users.toggle', $u->id) }}" method="POST" style="display: inline">
                    @csrf
                    <button class="btn btn-sm {{ $u->is_active ? 'btn-danger' : 'btn-success' }}" onclick="return confirm('{{ $u->is_active ? 'Suspend' : 'Activate' }} this user?')">
                      {{ $u->is_active ? 'Suspend' : 'Activate' }}
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