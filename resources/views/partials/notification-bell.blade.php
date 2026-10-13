{{-- Header bell: unread count + the latest notifications (no JS needed: <details>) --}}
@auth
  @php
    $bellUser   = auth()->user();
    $bellUnread = $bellUser->unreadNotifications()->count();
    $bellLatest = $bellUser->notifications()->limit(6)->get();
  @endphp
  <details class="bell">
    <summary class="bell-btn" title="{{ __('Notifications') }}" aria-label="{{ __('Notifications') }}">
      <i class="fas fa-bell"></i>
      @if($bellUnread)<b class="bell-count">{{ $bellUnread > 99 ? '99+' : $bellUnread }}</b>@endif
    </summary>
    <div class="bell-menu">
      <div class="bell-head">
        <strong>{{ __('Notifications') }}</strong>
        @if($bellUnread)
          <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button type="submit" class="bell-link">{{ __('Mark all as read') }}</button>
          </form>
        @endif
      </div>
      @forelse($bellLatest as $note)
        @include('notifications._item', ['note' => $note])
      @empty
        <p class="bell-empty">{{ __('No notifications yet.') }}</p>
      @endforelse
      <a href="{{ route('notifications.index') }}" class="bell-all">{{ __('See all notifications') }}</a>
    </div>
  </details>
@endauth
