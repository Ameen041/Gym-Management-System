@extends('layouts.panel')
@section('title', __('Messages'))

@section('content')
<div class="page-title">
  <div>
    <h1>{{ __('Messages') }}</h1>
    <p>{{ $me->role === 'trainer' ? __('Talk to your clients here. Their numbers stay private.') : __('Talk to your coach here.') }}</p>
  </div>
</div>

@include('partials.push-prompt')

<section class="panel chat-list">
  @forelse($conversations as $conversation)
    @php($other = $conversation->otherMember($me))
    @php($last = $lastMessages[$conversation->id] ?? null)
    @php($when = $last?->created_at->copy()->timezone(config('app.display_timezone')))
    <a href="{{ route('chat.show', $conversation) }}" class="chat-row {{ $conversation->unread_count ? 'unread' : '' }}">
      <img src="{{ $other->photo_url }}" alt="" class="avatar-sm">
      <div class="grow">
        <div class="chat-row-top">
          <strong>{{ $other->name }}</strong>
          @if($when)<time class="muted small" datetime="{{ $last->created_at->toIso8601String() }}">{{ $when->isToday() ? $when->format('H:i') : $when->translatedFormat('j M') }}</time>@endif
        </div>
        <div class="chat-row-last" dir="auto">
          @if($last)
            @if((int) $last->sender_id === (int) $me->id)<span class="muted">{{ __('You:') }}</span>@endif {{ \Illuminate\Support\Str::limit($last->body, 90) }}
          @else
            <span class="muted">{{ __('No messages yet') }}</span>
          @endif
        </div>
      </div>
      @if($conversation->unread_count)
        <b class="count-pill">{{ $conversation->unread_count }}</b>
      @elseif(!$open[$conversation->id])
        <span class="badge badge-muted"><i class="fas fa-lock"></i> {{ __('Closed') }}</span>
      @endif
    </a>
  @empty
    <div class="empty">
      <i class="fas fa-comments"></i>
      <p>{{ $me->role === 'trainer' ? __('Chats with your clients appear here once their payment is confirmed.') : __('The chat with your coach appears here once your payment is confirmed.') }}</p>
    </div>
  @endforelse
</section>
@endsection
