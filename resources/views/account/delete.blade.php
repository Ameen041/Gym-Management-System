@extends('layouts.panel')
@section('title', __('Delete account'))

@section('content')
<div class="page-title">
  <div><h1>{{ __('Delete your account') }}</h1></div>
</div>

<section class="panel delete-account">
  @if($blockers)
    <div class="alert alert-error" role="alert">
      <i class="fas fa-circle-exclamation"></i>
      <div>
        <strong>{{ __('You can\'t delete your account yet:') }}</strong>
        <ul>@foreach($blockers as $blocker)<li>{{ $blocker }}</li>@endforeach</ul>
      </div>
    </div>
    <a href="{{ url()->previous() }}" class="btn"><i class="fas fa-arrow-right-long flip-ltr"></i> {{ __('Back') }}</a>
  @else
    <p><strong>{{ __('This can\'t be undone.') }}</strong> {{ __('When you delete your account:') }}</p>
    <ul class="delete-list">
      <li>{{ __('Your name, email, phone, photos, health answers, check-ins and chats are deleted.') }}</li>
      @if($running)
        <li>{{ __('Your current subscription ends now, without a refund.') }}</li>
      @endif
      @if($user->role === 'trainer')
        <li>{{ __('Your profile and packages disappear from Himma.') }}</li>
      @else
        <li>{{ __('Your reviews stay, without your name.') }}</li>
      @endif
      <li>{{ __('Payment records (amounts, dates, transaction numbers and receipts) are kept for accounting, without your name.') }}</li>
    </ul>

    <form method="POST" action="{{ route('account.destroy') }}">
      @csrf
      @method('DELETE')
      <div class="form-group" style="max-width:360px">
        <label for="delete-password">{{ __('Password') }}</label>
        <input type="password" id="delete-password" name="password" required autocomplete="current-password">
      </div>
      <label class="check" style="margin-bottom:14px">
        <input type="checkbox" name="confirm" value="1" required>
        <span>{{ __('I understand that my account and my data will be deleted for good.') }}</span>
      </label>
      <div class="form-actions">
        <button type="submit" class="btn btn-danger"><i class="fas fa-user-xmark"></i> {{ __('Delete my account') }}</button>
        <a href="{{ url()->previous() }}" class="btn">{{ __('Cancel') }}</a>
      </div>
    </form>
  @endif
</section>
@endsection
