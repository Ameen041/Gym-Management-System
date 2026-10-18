@extends('layouts.admin')
@section('title', __('Chats'))
@section('page-title', __('Chats'))

@section('content')
<div class="page-header">
  <h1>{{ __('Chats') }}</h1>
  <p>{{ __('Chats are private. You can read one only after the coach or the client reports it. Messages that held a number, a link or another app were not sent; they are listed here.') }}</p>
</div>

<div class="card">
  <h3 style="margin-bottom:16px"><i class="fas fa-flag"></i> {{ __('Reported chats') }}</h3>
  @if($openReports->isEmpty())
    <div class="empty-state"><i class="fas fa-circle-check"></i><h3>{{ __('No open reports') }}</h3></div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead><tr><th>{{ __('Chat') }}</th><th>{{ __('Reported by') }}</th><th>{{ __('What happened') }}</th><th>{{ __('Date') }}</th><th></th></tr></thead>
        <tbody>
          @foreach($openReports as $report)
            <tr>
              <td><strong>{{ $report->conversation->coach->name }}</strong> ↔ {{ $report->conversation->trainee->name }}</td>
              <td>{{ $report->reporter->name }}</td>
              <td dir="auto" style="white-space:pre-line;max-width:360px">{{ $report->reason ?: '—' }}</td>
              <td>{{ $report->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</td>
              <td><a href="{{ route('admin.chats.show', $report->conversation) }}" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> {{ __('Read chat') }}</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@if($repeaters->isNotEmpty())
  <div class="card" style="margin-top:16px">
    <h3 style="margin-bottom:6px"><i class="fas fa-user-shield"></i> {{ __('Tried several times') }}</h3>
    <p style="color:var(--muted);margin-bottom:12px">{{ __('People who tried :count times or more in the last :days days to share contact details. Talk to them, or block the account from Users.', ['count' => \App\Models\ChatBlockedAttempt::ALERT_AFTER, 'days' => \App\Models\ChatBlockedAttempt::ALERT_DAYS]) }}</p>
    <div class="table-wrapper">
      <table class="table">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Role') }}</th><th>{{ __('Tries') }}</th><th></th></tr></thead>
        <tbody>
          @foreach($repeaters as $row)
            <tr>
              <td><strong>{{ $row->sender?->name }}</strong></td>
              <td>{{ $row->sender?->role === 'trainer' ? __('Coach') : __('Trainee') }}</td>
              <td>{{ $row->tries }}</td>
              <td>@if($row->sender)<a href="{{ route('admin.users.edit', $row->sender->id) }}" class="btn btn-sm btn-secondary">{{ __('Open account') }}</a>@endif</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif

<div class="card" style="margin-top:16px">
  <h3 style="margin-bottom:16px"><i class="fas fa-ban"></i> {{ __('Stopped messages') }}</h3>
  @if($attempts->isEmpty())
    <div class="empty-state"><i class="fas fa-shield-halved"></i><h3>{{ __('Nothing was stopped yet') }}</h3></div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead><tr><th>{{ __('Sender') }}</th><th>{{ __('Message') }}</th><th>{{ __('Why') }}</th><th>{{ __('Date') }}</th></tr></thead>
        <tbody>
          @foreach($attempts as $attempt)
            <tr>
              <td><strong>{{ $attempt->sender?->name }}</strong><div style="color:var(--muted);font-size:.8rem">{{ __('to :name', ['name' => $attempt->conversation->otherMember($attempt->sender)->name]) }}</div></td>
              <td dir="auto" style="white-space:pre-line;max-width:420px">{{ $attempt->body }}</td>
              <td><span class="badge badge-warning">{{ $attempt->reason_label }}</span></td>
              <td>{{ $attempt->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@if($oldReports->isNotEmpty())
  <div class="card" style="margin-top:16px">
    <h3 style="margin-bottom:16px">{{ __('Closed reports') }}</h3>
    <div class="table-wrapper">
      <table class="table">
        <tbody>
          @foreach($oldReports as $report)
            <tr>
              <td>{{ $report->conversation->coach->name }} ↔ {{ $report->conversation->trainee->name }}</td>
              <td>{{ $report->reporter->name }}</td>
              <td>{{ $report->resolved_at->format('Y-m-d') }}</td>
              <td><a href="{{ route('admin.chats.show', $report->conversation) }}" class="btn btn-sm btn-secondary">{{ __('Read chat') }}</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
@endif
@endsection
