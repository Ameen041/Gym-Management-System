@extends('layouts.admin')
@section('title', $coach->name)
@section('page-title', __('Coach profile'))

@section('content')
@php use App\Support\Money; @endphp
<div class="card">
  <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
    <img src="{{ $coach->photo_url }}" alt="" style="width:96px;height:96px;border-radius:50%;object-fit:cover">
    <div style="flex:1;min-width:220px">
      <h2 style="margin:0">{{ $coach->name }}</h2>
      <div style="color:var(--gray-700)">{{ $coach->email }} · <span dir="ltr">{{ $coach->phone ?: '—' }}</span></div>
      <div style="margin-top:6px">
        @switch($profile->status)
          @case('approved') <span class="badge badge-success">{{ __('Approved') }}</span> @break
          @case('rejected') <span class="badge badge-danger">{{ __('Rejected') }}</span> <span style="color:var(--gray-700)">{{ $profile->rejection_reason }}</span> @break
          @default <span class="badge badge-warning">{{ __('Waiting for approval') }}</span>
        @endswitch
      </div>
    </div>
    <a href="{{ route('coaches.show', $coach) }}" target="_blank" class="btn btn-secondary"><i class="fas fa-eye"></i> {{ __('Public profile') }}</a>
  </div>

  @if($missing)
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ __('Missing') }}: {{ implode('، ', $missing) }}</div>
  @endif

  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start">
    @if($profile->status !== 'approved')
      <form method="POST" action="{{ route('admin.coaches.approve', $coach) }}">
        @csrf
        <button class="btn btn-success"><i class="fas fa-check"></i> {{ __('Approve') }}</button>
      </form>
    @endif
    <form method="POST" action="{{ route('admin.coaches.reject', $coach) }}" style="display:flex;gap:8px;flex-wrap:wrap">
      @csrf
      <input type="text" name="reason" class="form-control" style="min-width:260px" placeholder="{{ __('Reason (shown to the coach)') }}" required>
      <button class="btn btn-danger"><i class="fas fa-times"></i> {{ $profile->status === 'approved' ? __('Hide profile') : __('Reject') }}</button>
    </form>
    <form method="POST" action="{{ route('admin.coaches.feature', $coach) }}">
      @csrf
      <button class="btn btn-warning"><i class="fas fa-crown"></i> {{ $profile->is_featured ? __('Remove from featured') : __('Make featured') }}</button>
    </form>
  </div>
</div>

<div class="card">
  <h3 style="margin-bottom:12px">{{ __('Profile') }}</h3>
  <p><strong>{{ __('Specialties') }}:</strong> {{ implode('، ', $profile->specialty_labels) ?: '—' }}</p>
  <p><strong>{{ __('City') }}:</strong> {{ $profile->city_label ?: '—' }} · <strong>{{ __('Gender') }}:</strong> {{ $profile->gender ? __(ucfirst($profile->gender)) : '—' }} · <strong>{{ __('Years of experience') }}:</strong> {{ $profile->experience_years ?? '—' }}</p>
  <p style="white-space:pre-line;margin-top:10px">{{ $profile->bio ?: '—' }}</p>
  @if(!empty($profile->certificates))
    <h4 style="margin:16px 0 8px">{{ __('Certificates') }}</h4>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      @foreach($profile->certificates as $path)
        <a href="{{ asset('storage/' . $path) }}" target="_blank"><img src="{{ asset('storage/' . $path) }}" alt="" style="width:140px;height:140px;object-fit:cover;border-radius:8px;border:1px solid var(--gray-400)"></a>
      @endforeach
    </div>
  @endif
</div>

<div class="card">
  <h3 style="margin-bottom:12px">{{ __('Packages') }}</h3>
  @forelse($packages as $package)
    <p>{{ $package->title }} — <strong>{{ Money::format($package->price) }}</strong> / {{ __(':days days', ['days' => $package->duration_days]) }} · {{ $package->includes_label }} @unless($package->is_active)<span class="badge badge-warning">{{ __('Hidden') }}</span>@endunless</p>
  @empty
    <p style="color:var(--gray-700)">{{ __('No packages yet.') }}</p>
  @endforelse
</div>

<div class="card">
  <h3 style="margin-bottom:12px">{{ __('Earnings') }}</h3>
  <p>{{ __('Total earned (after commission)') }}: <strong>{{ Money::format($earnings['earned']) }}</strong> · {{ __('Paid') }}: <strong>{{ Money::format($earnings['paid']) }}</strong> · {{ __('On hold') }}: <strong>{{ Money::format($earnings['pending']) }}</strong> · {{ __('Ready to pay') }}: <strong>{{ Money::format($earnings['available']) }}</strong></p>
  @if($subscriptions->isNotEmpty())
    <div class="table-wrapper" style="margin-top:12px">
      <table class="table">
        <thead><tr><th>{{ __('Trainee') }}</th><th>{{ __('Package') }}</th><th>{{ __('Price') }}</th><th>{{ __('Status') }}</th><th>{{ __('Date') }}</th></tr></thead>
        <tbody>
          @foreach($subscriptions as $sub)
            <tr><td>{{ $sub->trainee->name ?? '—' }}</td><td>{{ $sub->package_title }}</td><td>{{ Money::format($sub->price) }}</td><td>{{ $sub->status_label }}</td><td>{{ $sub->created_at->format('Y-m-d') }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
