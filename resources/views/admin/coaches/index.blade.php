@extends('layouts.admin')
@section('title', __('Coaches'))
@section('page-title', __('Coaches'))

@section('content')
<div class="page-header">
  <h1>{{ __('Coaches') }}</h1>
  <p>{{ __('Check new coach profiles and certificates before they appear to trainees.') }}</p>
</div>

<div class="card">
  <div class="tabs" style="margin-bottom:20px">
    @foreach(['pending' => __('Waiting for approval'), 'approved' => __('Approved'), 'rejected' => __('Rejected'), 'all' => __('All')] as $key => $label)
      <a href="{{ route('admin.coaches.index', ['status' => $key]) }}" class="tab {{ $status === $key ? 'active' : '' }}">
        {{ $label }} @if(isset($counts[$key]))({{ $counts[$key] }})@endif
      </a>
    @endforeach
  </div>

  @if($coaches->isEmpty())
    <div class="empty-state"><i class="fas fa-user-tie"></i><h3>{{ __('No coaches here') }}</h3></div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Coach') }}</th>
            <th>{{ __('Profile') }}</th>
            <th>{{ __('Active clients') }}</th>
            <th>{{ __('Rating') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($coaches as $coach)
            @php($profile = $coach->coachProfile)
            @php($missing = $profile ? $profile->missingSteps() : [__('Profile')])
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <img src="{{ $coach->photo_url }}" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                  <div><strong>{{ $coach->name }}</strong><div style="color:var(--gray-700);font-size:.85rem">{{ $coach->email }}</div></div>
                </div>
              </td>
              <td>
                @if($missing)
                  <span class="badge badge-warning">{{ __('Incomplete') }}</span>
                  <div style="color:var(--gray-700);font-size:.8rem">{{ implode('، ', $missing) }}</div>
                @else
                  <span class="badge badge-success">{{ __('Complete') }}</span>
                @endif
              </td>
              <td>{{ $coach->clients_count }}</td>
              <td>{{ $profile && $profile->rating_count ? number_format($profile->rating_avg, 1) . ' ★ (' . $profile->rating_count . ')' : '—' }}</td>
              <td>
                @switch($profile->status ?? 'pending')
                  @case('approved') <span class="badge badge-success">{{ __('Approved') }}</span> @break
                  @case('rejected') <span class="badge badge-danger">{{ __('Rejected') }}</span> @break
                  @default <span class="badge badge-warning">{{ __('Waiting for approval') }}</span>
                @endswitch
                @if($profile?->is_featured)<span class="badge badge-primary">★ {{ __('Featured') }}</span>@endif
              </td>
              <td><a href="{{ route('admin.coaches.show', $coach) }}" class="btn btn-sm btn-primary">{{ __('Review') }}</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
