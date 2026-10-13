<a href="{{ route('notifications.open', $note->id) }}" class="notif {{ $note->read_at ? '' : 'unread' }}">
  <span class="notif-icon"><i class="fas {{ $note->data['icon'] ?? 'fa-bell' }}"></i></span>
  <span class="notif-body">
    <span class="notif-text">{{ __($note->data['message'] ?? '', $note->data['params'] ?? []) }}</span>
    <small class="notif-time">{{ $note->created_at->diffForHumans() }}</small>
  </span>
</a>
