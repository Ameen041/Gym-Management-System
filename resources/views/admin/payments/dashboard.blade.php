@extends('layouts.admin')
@section('title', 'Payments Dashboard')
@section('page-title', 'Payments Dashboard')

@section('content')
<div class="page-header">
  <h1>Payments Dashboard</h1>
  <p>Financial overview and statistics</p>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.payments.dashboard') }}" class="filter-bar">
  <input
    type="date"
    name="from"
    value="{{ request('from') }}"
    class="filter-input date-fix"
  >

  <button type="submit" class="btn btn-primary">Filter</button>
  <a href="{{ route('admin.payments.dashboard') }}" class="btn btn-secondary">Reset</a>
</form>

{{-- Stats --}}
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon" style="background: var(--primary);">
      <i class="fas fa-calendar-day"></i>
    </div>
    <div class="stat-value">{{ number_format($dailyTotal, 0) }}</div>
    <div class="stat-label">Today's Payments</div>
    <div style="color: var(--gray-700); font-size: 0.875rem; margin-top: 8px;">
      {{ $dailyCount }} transactions
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: var(--secondary);">
      <i class="fas fa-calendar-alt"></i>
    </div>
    <div class="stat-value">{{ number_format($monthlyTotal, 0) }}</div>
    <div class="stat-label">This Month</div>
    <div style="color: var(--gray-700); font-size: 0.875rem; margin-top: 8px;">
      {{ $monthlyCount }} transactions
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background: var(--success);">
      <i class="fas fa-calendar"></i>
    </div>
    <div class="stat-value">{{ number_format($yearlyTotal, 0) }}</div>
    <div class="stat-label">This Year</div>
    <div style="color: var(--gray-700); font-size: 0.875rem; margin-top: 8px;">
      {{ $yearlyCount }} transactions
    </div>
  </div>
</div>

{{-- Latest Payments --}}
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom: 16px;">
    <h3 style="color: var(--dark); margin: 0;">Latest Payments</h3>
    <div style="color: var(--gray-700); font-size: 0.9rem;">
      Showing last {{ ($dailyLatest ?? collect())->count() }} records
    </div>
  </div>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>User</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Period</th>
        </tr>
      </thead>
      <tbody>
        @forelse($dailyLatest as $p)
          <tr>
            <td>{{ \Carbon\Carbon::parse($p->paid_at)->format('Y-m-d') }}</td>
            <td>{{ $p->user->name ?? '—' }}</td>
            <td><strong>{{ number_format($p->amount, 0) }}</strong></td>
            <td>{{ $p->method ?? '-' }}</td>
            <td>
              @if($p->period_start && $p->period_end)
                {{ \Carbon\Carbon::parse($p->period_start)->format('Y-m-d') }} →
                {{ \Carbon\Carbon::parse($p->period_end)->format('Y-m-d') }}
@else
                —
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align: center; padding: 20px; color: var(--gray-700);">
              <i class="fas fa-credit-card" style="font-size: 2rem; margin-bottom: 12px; display: block;"></i>
              No payments found
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Monthly breakdown --}}
<div class="card">
  <h3 style="color: var(--dark); margin-bottom: 20px;">Monthly Breakdown (Top Days)</h3>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>Day</th>
          <th>Total</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        @forelse($monthlyByDay as $row)
          <tr>
            <td>{{ $row->day }}</td>
            <td><strong>{{ number_format($row->total, 0) }}</strong></td>
            <td>{{ $row->count }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" style="text-align:center; padding:20px; color: var(--gray-700);">
              No data for this month
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Yearly breakdown --}}
<div class="card">
  <h3 style="color: var(--dark); margin-bottom: 20px;">Yearly Breakdown (by month)</h3>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>Month</th>
          <th>Total</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        @forelse($yearlyByMonth as $row)
          <tr>
            <td>{{ \Carbon\Carbon::create()->month($row->month)->format('M') }}</td>
            <td><strong>{{ number_format($row->total, 0) }}</strong></td>
            <td>{{ $row->count }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" style="text-align:center; padding:20px; color: var(--gray-700);">
              No data for this year
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- By method --}}
<div class="card">
  <h3 style="color: var(--dark); margin-bottom: 20px;">This Month by Method</h3>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>Method</th>
          <th>Total</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        @forelse($byMethod as $row)
          <tr>
            <td>{{ $row->method }}</td>
            <td><strong>{{ number_format($row->total, 0) }}</strong></td>
            <td>{{ $row->count }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="3" style="text-align:center; padding:20px; color: var(--gray-700);">
              No method data available
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection