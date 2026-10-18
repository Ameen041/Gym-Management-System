@extends('layouts.admin')
@section('title', __('Chats'))
@section('page-title', __('Chats'))

@section('content')
<div class="page-header">
  <h1>{{ $conversation->coach->name }} ↔ {{ $conversation->trainee->name }}</h1>
  <p>{{ __('Coach') }}: {{ $conversation->coach->name }} · {{ __('Trainee') }}: {{ $conversation->trainee->name }}</p>
</div>

<div class="card">
  <h3 style="margin-bottom:12px"><i class="fas fa-flag"></i> {{ __('Reports') }}</h3>
  @foreach($reports as $report)
    <p style="margin-bottom:8px">
      <strong>{{ $report->reporter->name }}</strong> · {{ $report->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}
      @if($report->resolved_at)<span class="badge badge-success">{{ __('Closed') }}</span>@else<span class="badge badge-warning">{{ __('Open') }}</span>@endif
      <br><span dir="auto" style="white-space:pre-line">{{ $report->reason ?: '—' }}</span>
    </p>
  @endforeach
  @if($reports->whereNull('resolved_at')->isNotEmpty())
    <form method="POST" action="{{ route('admin.chats.resolve', $conversation) }}">
      @csrf
      <button class="btn btn-success"><i class="fas fa-check"></i> {{ __('Close report and tell them') }}</button>
    </form>
  @endif
</div>

<div class="card admin-chat" style="margin-top:16px">
  <h3 style="margin-bottom:12px"><i class="fas fa-comments"></i> {{ __('Messages') }}</h3>
  @forelse($messages as $message)
    <div class="admin-msg {{ (int) $message->sender_id === (int) $conversation->coach_id ? 'from-coach' : '' }}">
      <div style="font-size:.8rem;color:var(--muted)"><strong>{{ $message->sender->name }}</strong> · {{ $message->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</div>
      <div dir="auto" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $message->body }}</div>
    </div>
  @empty
    <div class="empty-state"><i class="fas fa-comments"></i><h3>{{ __('No messages yet') }}</h3></div>
  @endforelse
</div>

@if($attempts->isNotEmpty())
  <div class="card" style="margin-top:16px">
    <h3 style="margin-bottom:12px"><i class="fas fa-ban"></i> {{ __('Stopped messages') }}</h3>
    @foreach($attempts as $attempt)
      <div class="admin-msg">
        <div style="font-size:.8rem;color:var(--muted)"><strong>{{ $attempt->sender?->name }}</strong> · {{ $attempt->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }} · {{ $attempt->reason_label }}</div>
        <div dir="auto" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $attempt->body }}</div>
      </div>
    @endforeach
  </div>
@endif
@endsection
