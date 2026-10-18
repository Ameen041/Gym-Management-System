@extends('layouts.panel')
@section('title', $other->name)

@section('content')
<div class="chat" data-chat data-poll="{{ route('chat.poll', $conversation) }}" data-last="{{ $messages->last()?->id ?? 0 }}" data-seen="{{ $seenId }}">
  <header class="chat-head">
    @if($me->role === 'trainee')
      <a href="{{ route('coaches.show', $other) }}"><img src="{{ $other->photo_url }}" alt="" class="avatar-sm"></a>
    @else
      <img src="{{ $other->photo_url }}" alt="" class="avatar-sm">
    @endif
    <div class="grow">
      <strong>{{ $other->name }}</strong>
      <div class="muted small">{{ $me->role === 'trainer' ? __('Client') : __('Your coach') }}@if($subscription) · {{ $subscription->package_title }}@endif</div>
    </div>
    @if($me->role === 'trainer' && $subscription)
      <a href="{{ route('coach.clients.show', $subscription) }}" class="btn btn-sm"><i class="fas fa-user"></i> {{ __('Client file') }}</a>
    @endif
    <details class="chat-more">
      <summary class="icon-btn" title="{{ __('Report') }}" aria-label="{{ __('Report') }}"><i class="fas fa-flag"></i></summary>
      <div class="chat-more-menu">
        @if($reported)
          <p class="small"><i class="fas fa-circle-check" style="color:var(--success)"></i> {{ __('You reported this chat. The Himma team will read it and contact you.') }}</p>
        @else
          <form method="POST" action="{{ route('chat.report', $conversation) }}">
            @csrf
            <strong>{{ __('Report a problem') }}</strong>
            <p class="muted small" style="margin:4px 0 8px">{{ __('The Himma team will read this chat to help you. Tell us what happened.') }}</p>
            <textarea name="reason" rows="3" maxlength="500" placeholder="{{ __('e.g. asked me to pay outside Himma') }}" dir="auto"></textarea>
            <button type="submit" class="btn btn-sm btn-danger" style="margin-top:8px"><i class="fas fa-flag"></i> {{ __('Send report') }}</button>
          </form>
        @endif
      </div>
    </details>
  </header>

  @include('partials.push-prompt')

  <p class="chat-rule"><i class="fas fa-shield-halved"></i> {{ __('Contact and payment stay on Himma: phone numbers, accounts and links can\'t be sent here. The Himma team reads a chat only if one of you reports it.') }}</p>

  <div class="chat-log" data-chat-log aria-live="polite">
    @if($hasOlder)
      <a href="{{ route('chat.show', [$conversation, 'before' => $messages->first()->id]) }}" class="chat-older"><i class="fas fa-clock-rotate-left"></i> {{ __('Earlier messages') }}</a>
    @endif
    @if($messages->isEmpty())
      <div class="chat-empty">
        <i class="fas fa-comments"></i>
        <p>{{ $me->role === 'trainer' ? __('Say hello to your client and tell them what comes next.') : __('Say hello to your coach. Ask anything about your plan here.') }}</p>
      </div>
    @endif
    @include('chat._messages', ['messages' => $messages, 'previousDay' => null])
  </div>

  @if($open)
    <form method="POST" action="{{ route('chat.store', $conversation) }}" class="chat-form" data-chat-form>
      @csrf
      <p class="chat-error" data-chat-error role="alert" hidden></p>
      <div class="chat-compose">
        <textarea name="body" rows="1" maxlength="{{ \App\Models\ChatMessage::MAX_LENGTH }}" required dir="auto"
                  placeholder="{{ __('Write a message…') }}" aria-label="{{ __('Message') }}">{{ old('body', $draft) }}</textarea>
        <button type="submit" class="btn btn-primary chat-send" title="{{ __('Send') }}" aria-label="{{ __('Send') }}"><i class="fas fa-paper-plane"></i></button>
      </div>
    </form>
  @else
    <div class="chat-closed">
      <i class="fas fa-lock"></i>
      <span class="grow">{{ __('This chat is closed: the subscription ended. Renew to keep talking.') }}</span>
      @if($me->role === 'trainee')
        <a href="{{ route('trainee.dashboard') }}" class="btn btn-sm btn-primary"><i class="fas fa-rotate-right"></i> {{ __('Renew') }}</a>
      @endif
    </div>
  @endif
</div>
@endsection

@push('scripts')
  <script src="{{ \App\Support\Asset::url('js/chat.js') }}" defer></script>
@endpush
