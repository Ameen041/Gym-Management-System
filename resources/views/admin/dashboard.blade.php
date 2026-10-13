@extends('layouts.admin')
@section('title', __('Dashboard'))
@section('page-title', __('Dashboard Overview'))

@section('content')
@php use App\Support\Money; @endphp
<div class="stats-grid">
  <a class="stat-card" href="{{ route('admin.subscriptions.index', ['tab' => 'review']) }}" style="text-decoration:none;color:inherit">
    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
    <div class="stat-value">{{ $stats['payments_to_review'] }}</div>
    <div class="stat-label">{{ __('Payments to confirm') }}</div>
  </a>
  <a class="stat-card" href="{{ route('admin.coaches.index') }}" style="text-decoration:none;color:inherit">
    <div class="stat-icon"><i class="fas fa-user-check"></i></div>
    <div class="stat-value">{{ $stats['coaches_pending'] }}</div>
    <div class="stat-label">{{ __('Coaches waiting for approval') }}</div>
  </a>
  <a class="stat-card" href="{{ route('admin.subscriptions.index', ['tab' => 'running']) }}" style="text-decoration:none;color:inherit">
    <div class="stat-icon"><i class="fas fa-bolt"></i></div>
    <div class="stat-value">{{ $stats['active_subs'] }}</div>
    <div class="stat-label">{{ __('Active subscriptions') }}</div>
  </a>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-coins"></i></div>
    <div class="stat-value">{{ Money::format($stats['month_commission']) }}</div>
    <div class="stat-label">{{ __('Platform commission this month') }} ({{ __('sales') }}: {{ Money::format($stats['month_sales']) }})</div>
  </div>
</div>

@if($stats['late_plans'])
  <div class="alert alert-error">
    <i class="fas fa-exclamation-triangle"></i>
    {{ __(':count active subscriptions are still waiting for a plan after :days days.', ['count' => $stats['late_plans'], 'days' => \App\Models\Subscription::PLAN_DUE_DAYS]) }}
  </div>
@endif

<div class="card">
  <h3 style="margin-bottom: 20px; color: var(--dark);">{{ __('Quick Actions') }}</h3>
  <div style="display:flex;gap:12px;flex-wrap:wrap;">
    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-primary"><i class="fas fa-receipt"></i> {{ __('Review payments') }}</a>
    <a href="{{ route('admin.coaches.index') }}" class="btn btn-success"><i class="fas fa-user-check"></i> {{ __('Review coaches') }}</a>
    <a href="{{ route('admin.payouts.index') }}" class="btn btn-warning"><i class="fas fa-hand-holding-usd"></i> {{ __('Coach payouts') }}</a>
    <a href="{{ route('admin.settings.edit') }}" class="btn btn-secondary"><i class="fas fa-cog"></i> {{ __('Settings') }}</a>
  </div>
  <p style="margin-top:16px;color:var(--gray-700)">
    {{ __('Coaches') }}: <strong>{{ $stats['coaches'] }}</strong> ·
    {{ __('Trainees') }}: <strong>{{ $stats['trainees'] }}</strong> ·
    {{ __('Unread messages') }}: <strong>{{ $stats['unread_messages'] }}</strong>
  </p>
</div>
@endsection
