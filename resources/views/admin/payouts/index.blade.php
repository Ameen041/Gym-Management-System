@extends('layouts.admin')
@section('title', __('Coach payouts'))
@section('page-title', __('Coach payouts'))

@section('content')
@php use App\Support\Money; @endphp
<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-shopping-cart"></i></div><div class="stat-value">{{ Money::format($totals['sales']) }}</div><div class="stat-label">{{ __('Total sales') }}</div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-coins"></i></div><div class="stat-value">{{ Money::format($totals['commission']) }}</div><div class="stat-label">{{ __('Platform commission') }}</div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-hand-holding-usd"></i></div><div class="stat-value">{{ Money::format($totals['owed']) }}</div><div class="stat-label">{{ __('Owed to coaches') }}</div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-paper-plane"></i></div><div class="stat-value">{{ Money::format($totals['available']) }}</div><div class="stat-label">{{ __('Ready to pay now') }}</div></div>
</div>

<div class="alert alert-info" style="margin-bottom:16px">
  <i class="fas fa-info-circle"></i>
  {{ __('Suggested routine: once a week, send each coach the amount under "Ready to pay" on Sham Cash, then record it here. Money is ready :days days after you confirm the payment, and only once the coach has sent the plan.', ['days' => \App\Models\Setting::get('payout_hold_days')]) }}
</div>

<div class="card">
  <h3 style="margin-bottom:16px">{{ __('Coach balances') }}</h3>
  @if($coaches->isEmpty())
    <div class="empty-state"><i class="fas fa-wallet"></i><h3>{{ __('No earnings yet') }}</h3></div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead><tr><th>{{ __('Coach') }}</th><th>{{ __('Earned') }}</th><th>{{ __('Paid') }}</th><th>{{ __('On hold') }}</th><th>{{ __('Ready to pay') }}</th><th>{{ __('Send payout') }}</th></tr></thead>
        <tbody>
          @foreach($coaches as $coach)
            <tr>
              <td><strong>{{ $coach->name }}</strong><div style="color:var(--gray-700);font-size:.8rem" dir="ltr">{{ $coach->phone }}</div></td>
              <td>{{ Money::format($coach->earnings['earned']) }}</td>
              <td>{{ Money::format($coach->earnings['paid']) }}</td>
              <td>{{ Money::format($coach->earnings['pending']) }}</td>
              <td><strong>{{ Money::format($coach->earnings['available']) }}</strong></td>
              <td>
                @if($coach->earnings['balance'] > 0)
                  <form method="POST" action="{{ route('admin.payouts.store') }}" style="display:flex;gap:6px;flex-wrap:wrap"
                        onsubmit="return confirm(@js(__('Record this payout? Send the money on Sham Cash first.')))">
                    @csrf
                    <input type="hidden" name="coach_id" value="{{ $coach->id }}">
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ $coach->earnings['available'] > 0 ? $coach->earnings['available'] : $coach->earnings['balance'] }}" max="{{ $coach->earnings['balance'] }}" class="form-control" style="width:110px;padding:6px 8px" required>
                    <input type="text" name="reference" class="form-control" style="width:150px;padding:6px 8px" placeholder="{{ __('Transaction number') }}">
                    <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> {{ __('Paid') }}</button>
                  </form>
                @else
                  <span style="color:var(--gray-700)">—</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<div class="card">
  <h3 style="margin-bottom:16px">{{ __('Payout history') }}</h3>
  @forelse($payouts as $payout)
    <p>{{ $payout->created_at->format('Y-m-d') }} — <strong>{{ $payout->coach->name ?? '—' }}</strong>: {{ Money::format($payout->amount) }} @if($payout->reference)<span dir="ltr">({{ $payout->reference }})</span>@endif</p>
  @empty
    <p style="color:var(--gray-700)">{{ __('No payouts yet.') }}</p>
  @endforelse
</div>
@endsection
