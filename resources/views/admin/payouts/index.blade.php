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
        <thead><tr><th>{{ __('Coach') }}</th><th>{{ __('Sham Cash account') }}</th><th>{{ __('Earned') }}</th><th>{{ __('Paid') }}</th><th>{{ __('On hold') }}</th><th>{{ __('Ready to pay') }}</th><th>{{ __('Send payout') }}</th></tr></thead>
        <tbody>
          @foreach($coaches as $coach)
            <tr>
              <td><strong>{{ $coach->name }}</strong><div style="color:var(--gray-700);font-size:.8rem" dir="ltr">{{ $coach->phone }}</div></td>
              <td class="payout-account">
                @php($p = $coach->coachProfile)
                @if($p?->hasPayoutAccount())
                  <div>{{ $p->shamcash_name }}</div>
                  <div class="copy-row" style="margin-top:4px">
                    <input type="text" id="acct-{{ $coach->id }}" value="{{ $p->shamcash_account }}" readonly dir="ltr" onfocus="this.select()">
                    <button type="button" class="btn btn-sm btn-secondary" data-copy="acct-{{ $coach->id }}" data-copied="{{ __('Copied') }}"><i class="fas fa-copy"></i> <span>{{ __('Copy') }}</span></button>
                    @if($p->shamcash_qr)
                      <a href="{{ route('coaches.payout-qr', $coach) }}" target="_blank" class="btn btn-sm btn-secondary" title="{{ __('Account QR code') }}"><i class="fas fa-qrcode"></i></a>
                    @endif
                  </div>
                  @if($p->payoutAccountChangedRecently())
                    <span class="badge badge-warning" style="margin-top:4px"><i class="fas fa-triangle-exclamation"></i> {{ __('Changed :when: confirm with the coach', ['when' => $p->payout_account_updated_at->diffForHumans()]) }}</span>
                  @endif
                @else
                  <span class="badge badge-danger">{{ __('No account yet') }}</span>
                  @if($coach->whatsapp_url)
                    <a href="{{ $coach->whatsapp_url }}?text={{ rawurlencode(__('Hi :name, please add your Sham Cash account in your profile (My Profile, bottom of the page) so we can send your earnings.', ['name' => $coach->name])) }}" target="_blank" rel="noopener" class="btn btn-sm btn-secondary" style="margin-top:4px"><i class="fab fa-whatsapp"></i> {{ __('Ask on WhatsApp') }}</a>
                  @endif
                @endif
              </td>
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
    <p>{{ $payout->created_at->format('Y-m-d') }} — <strong>{{ $payout->coach->name ?? '—' }}</strong>: {{ Money::format($payout->amount) }} @if($payout->reference)<span dir="ltr">({{ $payout->reference }})</span>@endif
      @if($payout->account)<span style="color:var(--gray-700);font-size:.85rem">· {{ __('Sent to') }} <span dir="ltr">{{ $payout->account }}</span></span>@endif</p>
  @empty
    <p style="color:var(--gray-700)">{{ __('No payouts yet.') }}</p>
  @endforelse
</div>
@endsection
