@extends('layouts.admin')
@section('title', __('Subscriptions'))
@section('page-title', __('Subscriptions & payments'))

@section('content')
@php use App\Support\Money; @endphp
<div class="page-header">
  <h1>{{ __('Subscriptions & payments') }}</h1>
  <p>{{ __('Check each Sham Cash receipt in your Sham Cash account, then confirm or reject it.') }}</p>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px">
    <div class="tabs">
      @foreach(['review' => __('Payments to confirm'), 'running' => __('Active'), 'awaiting' => __('Not paid yet'), 'all' => __('All')] as $key => $label)
        <a href="{{ route('admin.subscriptions.index', ['tab' => $key]) }}" class="tab {{ $tab === $key ? 'active' : '' }}">
          {{ $label }} @if(isset($counts[$key]))({{ $counts[$key] }})@endif
        </a>
      @endforeach
    </div>
    <form method="GET" class="filter-bar" style="margin:0">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <input type="text" name="q" value="{{ $search }}" class="filter-input" placeholder="{{ __('Trainee, coach or transaction number') }}">
      <button class="btn btn-primary">{{ __('Search') }}</button>
    </form>
  </div>

  @if($subscriptions->isEmpty())
    <div class="empty-state"><i class="fas fa-receipt"></i><h3>{{ __('Nothing here') }}</h3></div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Trainee') }}</th>
            <th>{{ __('Coach') }}</th>
            <th>{{ __('Package') }}</th>
            <th>{{ __('Amount') }}</th>
            <th>{{ __('Receipt') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($subscriptions as $sub)
            <tr>
              <td>{{ $sub->id }}</td>
              <td><strong>{{ $sub->trainee->name ?? '—' }}</strong><div style="color:var(--gray-700);font-size:.8rem" dir="ltr">{{ $sub->trainee->phone ?? '' }}</div></td>
              <td>{{ $sub->coach->name ?? '—' }}</td>
              <td>{{ $sub->package_title }}<div style="color:var(--gray-700);font-size:.8rem">{{ __(':days days', ['days' => $sub->duration_days]) }}</div></td>
              <td><strong>{{ Money::format($sub->price) }}</strong>
                @if($sub->commission_amount !== null)
                  <div style="color:var(--gray-700);font-size:.8rem">{{ __('Commission') }}: {{ Money::format($sub->commission_amount) }}</div>
                @endif
              </td>
              <td>
                @if($sub->payment_proof)
                  <a href="{{ $sub->proofUrl() }}" target="_blank"><img src="{{ $sub->proofUrl() }}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid var(--gray-400)"></a>
                @endif
                @if($sub->payment_reference)<div style="font-size:.8rem" dir="ltr">{{ $sub->payment_reference }}</div>@endif
                @if($sub->payment_submitted_at)<div style="color:var(--gray-700);font-size:.75rem">{{ $sub->payment_submitted_at->format('Y-m-d H:i') }}</div>@endif
              </td>
              <td>
                @php($badge = ['active' => 'badge-success', 'payment_review' => 'badge-warning', 'rejected' => 'badge-danger', 'expired' => 'badge-primary'][$sub->display_status] ?? 'badge-primary')
                <span class="badge {{ $badge }}">{{ $sub->status_label }}</span>
                @if($sub->ends_at)<div style="color:var(--gray-700);font-size:.75rem">{{ $sub->starts_at->format('Y-m-d') }} → {{ $sub->ends_at->format('Y-m-d') }}</div>@endif
                @if($sub->admin_note)<div style="color:var(--danger);font-size:.75rem">{{ $sub->admin_note }}</div>@endif
              </td>
              <td>
                @if($sub->status === 'payment_review')
                  <form method="POST" action="{{ route('admin.subscriptions.confirm', $sub) }}" style="margin-bottom:6px"
                        onsubmit="return confirm(@js(__('Did you receive :amount on Sham Cash?', ['amount' => Money::format($sub->price)])))">
                    @csrf
                    <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> {{ __('Confirm') }}</button>
                  </form>
                  <form method="POST" action="{{ route('admin.subscriptions.reject', $sub) }}" style="display:flex;gap:4px">
                    @csrf
                    <input type="text" name="reason" class="form-control" style="min-width:140px;padding:6px 8px" placeholder="{{ __('Reason') }}" required>
                    <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="margin-top:16px">{{ $subscriptions->links('pagination::simple-default') }}</div>
  @endif
</div>
@endsection
