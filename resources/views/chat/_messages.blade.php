{{-- Chat bubbles: $messages (oldest first), $me, $previousDay (local date of the message before them, or null) --}}
@foreach($messages as $message)
  @php($local = $message->created_at->copy()->timezone(config('app.display_timezone')))
  @if($local->toDateString() !== $previousDay)
    @php($previousDay = $local->toDateString())
    <div class="chat-day"><span>{{ $local->isToday() ? __('Today') : ($local->isYesterday() ? __('Yesterday') : $local->translatedFormat('l j M')) }}</span></div>
  @endif
  @php($mine = (int) $message->sender_id === (int) $me->id)
  <div class="msg {{ $mine ? 'msg-mine' : '' }}" id="m{{ $message->id }}" data-id="{{ $message->id }}">
    <div class="msg-body" dir="auto">{{ $message->body }}</div>
    <div class="msg-meta">
      <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $local->format('H:i') }}</time>
      @if($mine)
        <i class="fas {{ $message->read_at ? 'fa-check-double seen' : 'fa-check' }} msg-tick" title="{{ $message->read_at ? __('Seen') : __('Sent') }}" data-seen-label="{{ __('Seen') }}"></i>
      @endif
    </div>
  </div>
@endforeach
