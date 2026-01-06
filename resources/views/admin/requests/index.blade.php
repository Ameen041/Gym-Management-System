@extends('layouts.admin')
@section('title', 'Requests')
@section('page-title', 'Requests Management')

@section('content')
<div class="page-header">
  <h1>Requests Management</h1>
  <p>Handle user requests and applications</p>
</div>

<div class="card">
  <div style="margin-bottom: 24px;">
    <h2 style="color: var(--dark); margin-bottom: 12px;">Requests</h2>
    
    <form method="GET" class="filter-bar">
      <input type="text" name="name" class="filter-input" placeholder="Search by trainee name" value="{{ request('name') }}">
      <button class="btn btn-primary" type="submit">Filter</button>
      
      @if(request('name'))
        <a href="{{ route('admin.requests.index') }}" class="btn btn-secondary">
          Reset
        </a>
      @endif
    </form>
  </div>

  <div class="tabs">
    <a href="{{ route('admin.requests.index', ['view' => 'pending']) }}" class="tab {{ ($view ?? 'pending') === 'pending' ? 'active' : '' }}">
      Pending
    </a>
    <a href="{{ route('admin.requests.index', ['view' => 'history']) }}" class="tab {{ ($view ?? 'pending') === 'history' ? 'active' : '' }}">
      History
    </a>
  </div>

  @if($requests->isEmpty())
    <div class="empty-state">
      <i class="fas fa-inbox"></i>
      <h3>No requests found</h3>
      <p>There are no requests matching your criteria</p>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Trainee</th>
            <th>Trainer</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($requests as $r)
            <tr>
              <td>{{ $r->id }}</td>
              <td>
                <span class="badge {{ $r->type === 'subscription' ? 'badge-primary' : 'badge-success' }}">
                  {{ ucfirst($r->type) }}
                </span>
              </td>
              <td>{{ $r->trainee_name }}</td>
              <td>{{ $r->trainer_name }}</td>
              <td>
                @if($r->status === 'pending')
                  <span class="badge badge-warning">Pending</span>
                @elseif($r->status === 'approved')
                  <span class="badge badge-success">Approved</span>
                @else
                  <span class="badge badge-danger">Rejected</span>
                @endif
              </td>
              <td>{{ \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i') }}</td>
              <td>
                @if(($view ?? 'pending') === 'pending')
                  <form action="{{ route('admin.requests.destroy', ['type' => $r->type, 'id' => $r->id]) }}" method="POST" style="display: inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this request?')">
                      <i class="fas fa-trash"></i>
                      Delete
                    </button>
                  </form>
                @else
                  <span style="color: var(--gray-700); font-size: 0.875rem;">— Archived —</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection