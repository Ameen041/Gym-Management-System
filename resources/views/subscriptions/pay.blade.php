@extends('layouts.panel')
@section('title', __('Payment'))

@section('content')
@php($amount = \App\Support\Money::format($subscription->price))

<div class="page-title">
  <h1>{{ __('Pay with Sham Cash') }}</h1>
  <p>{{ __('Step 2 of 2') }}</p>
</div>

@if($subscription->status === 'rejected')
  <div class="alert alert-error">
    <i class="fas fa-times-circle"></i>
    <div>
      <strong>{{ __('Your previous receipt was not accepted.') }}</strong>
      @if($subscription->admin_note)<div>{{ $subscription->admin_note }}</div>@endif
      <div>{{ __('Please check the transfer and upload a new receipt.') }}</div>
    </div>
  </div>
@endif

<div class="grid-2" style="align-items:start">
  <section class="panel stack">
    <div class="panel-head"><h2><i class="fas fa-wallet"></i> {{ __('Transfer details') }}</h2></div>

    <div class="stat" style="box-shadow:none">
      <div class="label">{{ __('Amount to transfer') }}</div>
      <div class="value">{{ $amount }}</div>
      <div class="muted small">{{ $subscription->package_title }} · {{ $subscription->coach->name }}</div>
    </div>

    @if($payment['account'])
      <div>
        <div class="muted small" style="margin-bottom:4px">{{ __('Sham Cash account') }}</div>
        <div class="copy-box">
          <span id="shamAccount">{{ $payment['account'] }}</span>
          <button type="button" class="btn btn-sm btn-light" data-copy="#shamAccount"><i class="fas fa-copy"></i> {{ __('Copy') }}</button>
        </div>
        @if($payment['name'])<div class="muted small" style="margin-top:4px">{{ __('Account name') }}: <strong>{{ $payment['name'] }}</strong></div>@endif
      </div>
    @else
      <div class="alert alert-warning"><i class="fas fa-info-circle"></i><div>{{ __('The payment account is not set yet. Please contact us.') }}</div></div>
    @endif

    @if($payment['qr'])
      <div style="text-align:center">
        <img src="{{ asset($payment['qr']) }}" alt="{{ __('Sham Cash QR code') }}" style="max-width:220px;border-radius:12px;border:1px solid var(--border)">
      </div>
    @endif

    <ol class="steps-list">
      <li>{{ __('Open the Sham Cash app and send :amount to the account above.', ['amount' => $amount]) }}</li>
      <li>{{ __('Take a screenshot of the successful transfer.') }}</li>
      <li>{{ __('Upload it here. We confirm payments as fast as possible.') }}</li>
    </ol>

    @if($payment['instructions'])
      <p class="muted small" style="white-space:pre-line">{{ $payment['instructions'] }}</p>
    @endif
  </section>

  <section class="stack">
    <form class="panel" method="POST" action="{{ route('subscriptions.pay.submit', $subscription) }}" enctype="multipart/form-data">
      @csrf
      <div class="panel-head"><h2><i class="fas fa-receipt"></i> {{ __('Upload the receipt') }}</h2></div>
      <div class="form-group">
        <label>{{ __('Transfer screenshot') }}</label>
        <input type="file" name="payment_proof" accept="image/*" required>
      </div>
      <div class="form-group">
        <label>{{ __('Transaction number (optional)') }}</label>
        <input type="text" name="payment_reference" value="{{ old('payment_reference', $subscription->payment_reference) }}" dir="ltr">
      </div>
      <button type="submit" class="btn btn-success btn-block"><i class="fas fa-paper-plane"></i> {{ __('Send for confirmation') }}</button>
      <p class="muted small" style="margin-top:10px">
        <i class="fas fa-rotate-left"></i>
        {{ __('If your coach doesn\'t send the plan within :days days, you get your money back.', ['days' => \App\Models\Subscription::PLAN_DUE_DAYS]) }}
        <a href="{{ route('terms') }}#refunds" target="_blank">{{ __('Refund policy') }}</a>
      </p>
    </form>

    <form method="POST" action="{{ route('subscriptions.cancel', $subscription) }}" onsubmit="return confirm(@js(__('Cancel this subscription?')))">
      @csrf
      <button type="submit" class="btn btn-light btn-block"><i class="fas fa-times"></i> {{ __('Cancel subscription') }}</button>
    </form>
  </section>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = document.querySelector(btn.dataset.copy).textContent.trim();
      if (navigator.clipboard) navigator.clipboard.writeText(text);
      btn.innerHTML = '<i class="fas fa-check"></i>';
    });
  });
</script>
@endpush
