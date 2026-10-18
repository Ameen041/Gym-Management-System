@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.panel')
@section('title', __('Notifications'))
@section('page-title', __('Notifications'))

@section('content')
<div class="notif-page">
  <div class="notif-page-head">
    <h1><i class="fas fa-bell"></i> {{ __('Notifications') }}</h1>
    @if(auth()->user()->unreadNotifications()->exists())
      <form method="POST" action="{{ route('notifications.readAll') }}">
        @csrf
        <button type="submit" class="notif-btn"><i class="fas fa-check-double"></i> {{ __('Mark all as read') }}</button>
      </form>
    @endif
  </div>
  @include('partials.push-prompt')

  <div class="notif-list">
    @forelse($notifications as $note)
      @include('notifications._item', ['note' => $note])
    @empty
      <p class="bell-empty"><i class="fas fa-bell-slash"></i> {{ __('No notifications yet.') }}</p>
    @endforelse
  </div>

  @if($notifications->hasPages())
    <div class="notif-pages">
      @if(!$notifications->onFirstPage())
        <a href="{{ $notifications->previousPageUrl() }}" class="notif-btn">{{ __('Previous') }}</a>
      @endif
      @if($notifications->hasMorePages())
        <a href="{{ $notifications->nextPageUrl() }}" class="notif-btn">{{ __('Next') }}</a>
      @endif
    </div>
  @endif
</div>
@endsection
