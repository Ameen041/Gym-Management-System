@extends('layouts.admin')
@section('title', __('Requests'))
@section('page-title', __('Requests Management'))

@section('content')
<div class="page-header">
  <h1>{{ __('Requests Management') }}</h1>
  <p>{{ __('Handle user requests and applications') }}</p>
</div>

<div class="card">
  <div style="margin-bottom: 24px;">
    <h2 style="color: var(--dark); margin-bottom: 12px;">{{ __('Requests') }}</h2>
    
    <form method="GET" class="filter-bar">
      <input type="text" name="name" class="filter-input" placeholder="{{ __('Search by trainee name') }}" value="{{ request('name') }}">
      <button class="btn btn-primary" type="submit">{{ __('Filter') }}</button>
      
      @if(request('name'))
        <a href="{{ route('admin.requests.index') }}" class="btn btn-secondary">
          {{ __('Reset') }}
        </a>
      @endif
    </form>
  </div>

  <div class="tabs">
    <a href="{{ route('admin.requests.index', ['view' => 'pending']) }}" class="tab {{ ($view ?? 'pending') === 'pending' ? 'active' : '' }}">
      {{ __('Pending') }}
    </a>
    <a href="{{ route('admin.requests.index', ['view' => 'history']) }}" class="tab {{ ($view ?? 'pending') === 'history' ? 'active' : '' }}">
      {{ __('History') }}
    </a>
  </div>

  @if($requests->isEmpty())
    <div class="empty-state">
      <i class="fas fa-inbox"></i>
      <h3>{{ __('No requests found') }}</h3>
      <p>{{ __('There are no requests matching your criteria') }}</p>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Trainee') }}</th>
            <th>{{ __('Trainer') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($requests as $r)
            <tr>
              <td>{{ $r->id }}</td>
              <td>
                <span class="badge {{ $r->type === 'subscription' ? 'badge-primary' : 'badge-success' }}">
                  {{ __(ucfirst($r->type)) }}
                </span>
              </td>
              <td>{{ $r->trainee_name }}</td>
              <td>{{ $r->trainer_name }}</td>
              <td>
                @if($r->status === 'pending')
                  <span class="badge badge-warning">{{ __('Pending') }}</span>
                @elseif($r->status === 'approved')
                  <span class="badge badge-success">{{ __('Approved') }}</span>
                @else
                  <span class="badge badge-danger">{{ __('Rejected') }}</span>
                @endif
              </td>
              <td>{{ \Carbon\Carbon::parse($r->created_at)->format('Y-m-d H:i') }}</td>
              <td>
                @if(($view ?? 'pending') === 'pending')
                  <form action="{{ route('admin.requests.destroy', ['type' => $r->type, 'id' => $r->id]) }}" method="POST" style="display: inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger" onclick="return confirm(@js(__('Delete this request?')))">
                      <i class="fas fa-trash"></i>
                      {{ __('Delete') }}
                    </button>
                  </form>
                @else
                  <span style="color: var(--gray-700); font-size: 0.875rem;">{{ __('— Archived —') }}</span>
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