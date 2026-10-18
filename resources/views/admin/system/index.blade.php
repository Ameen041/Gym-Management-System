@extends('layouts.admin')
@section('title', __('System'))
@section('page-title', __('System'))

@section('content')
<div class="page-header">
  <h1>{{ __('System') }}</h1>
  <p>{{ $isLive ? __('Live site') : __('Local copy: some checks only matter on the live site.') }}</p>
</div>

<section class="card">
  <div class="sys-head">
    <h3><i class="fas fa-list-check"></i> {{ __('Ready to launch') }}</h3>
    <span class="badge {{ $passed === count($checks) ? 'badge-ok' : 'badge-warn' }}">{{ $passed }} / {{ count($checks) }}</span>
  </div>
  @foreach($checks as [$ok, $title, $hint])
    <div class="list-row sys-check">
      <i class="fas {{ $ok ? 'fa-circle-check is-ok' : 'fa-circle-xmark is-bad' }}"></i>
      <div class="grow">
        <strong>{{ $title }}</strong>
        @unless($ok)<div class="muted small">{{ $hint }}</div>@endunless
      </div>
    </div>
  @endforeach
</section>

<section class="card">
  <div class="sys-head">
    <h3><i class="fas fa-database"></i> {{ __('Backups') }}</h3>
    <form method="POST" action="{{ route('admin.system.backup') }}">
      @csrf
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('Back up now') }}</button>
    </form>
  </div>
  <p class="muted small">{{ __('A backup of the database is made every night and the last 14 are kept. Download one every week and keep it off the server: it holds all accounts and payments, so keep it private.') }}</p>

  @forelse($backups as $b)
    <div class="list-row">
      <i class="fas fa-file-zipper muted"></i>
      <div class="grow">
        <strong dir="ltr">{{ $b['time']->format('Y-m-d H:i') }}</strong>
        <div class="muted small">{{ $b['time']->diffForHumans() }} · {{ number_format($b['size'] / 1024, 0) }} KB @if(str_contains($b['name'], 'before-restore'))· {{ __('Taken before a restore') }}@endif</div>
      </div>
      <a href="{{ route('admin.system.download', $b['name']) }}" class="btn btn-secondary btn-sm"><i class="fas fa-download"></i> {{ __('Download') }}</a>
      <form method="POST" action="{{ route('admin.system.destroy', $b['name']) }}" onsubmit="return confirm(@js(__('Delete this backup?')))">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm" aria-label="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
      </form>
    </div>
  @empty
    <div class="empty-state"><i class="fas fa-database"></i><h3>{{ __('No backups yet') }}</h3></div>
  @endforelse

  <details class="fold sys-restore">
    <summary>{{ __('How to restore a backup') }}</summary>
    <p class="muted small">{{ __('Restoring replaces all current data, so it is done from the server console. The current data is backed up first.') }}</p>
    <pre dir="ltr">php artisan app:restore himma-YYYY-MM-DD-HHMMSS.json.gz</pre>
  </details>
</section>
@endsection
