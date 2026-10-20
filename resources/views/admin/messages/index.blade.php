@extends('layouts.admin')
@section('title', __('Messages'))
@section('page-title', __('Contact Messages'))

@section('content')
<div class="page-header">
  <h1>{{ __('Messages') }}</h1>
  <p>{{ __('Messages sent from the contact form on the home page') }}</p>
</div>

<div class="card">
  @if($messages->isEmpty())
    <div class="empty-state">
      <i class="fas fa-inbox"></i>
      <h3>{{ __('No messages yet') }}</h3>
    </div>
  @else
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Sender') }}</th>
            <th>{{ __('Message') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($messages as $msg)
            <tr style="{{ $msg->is_read ? '' : 'background: rgba(67, 97, 238, 0.06);' }}">
              <td>
                <strong>{{ $msg->name }}</strong>
                @unless($msg->is_read)
                  <span class="badge badge-primary">{{ __('New') }}</span>
                @endunless
                <p style="color: var(--gray-700); margin-top: 4px; font-size: 0.9rem;" dir="ltr">
                  <a href="mailto:{{ $msg->email }}">{{ $msg->email }}</a>
                  @if($msg->phone) · {{ $msg->phone }} @endif
                </p>
              </td>
              <td style="white-space: pre-line; max-width: 480px;">{{ $msg->message }}</td>
              <td>{{ $msg->created_at->format('Y-m-d H:i') }}</td>
              <td>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                  @unless($msg->is_read)
                    <form action="{{ route('admin.messages.read', $msg) }}" method="POST">
                      @csrf
                      <button class="btn btn-sm btn-secondary"><i class="fas fa-check"></i> {{ __('Mark as read') }}</button>
                    </form>
                  @endunless
                  <form action="{{ route('admin.messages.destroy', $msg) }}" method="POST"
                        onsubmit="return confirm(@js(__('Delete this message?')))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> {{ __('Delete') }}</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    {{ $messages->links('partials.pager') }}
  @endif
</div>
@endsection
