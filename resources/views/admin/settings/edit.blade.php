@extends('layouts.admin')
@section('title', __('Settings'))
@section('page-title', __('Platform settings'))

@section('content')
<form class="card" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
  @csrf
  @if($errors->any())
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
  @endif

  <h3 style="margin-bottom:16px"><i class="fas fa-percent"></i> {{ __('Commission') }}</h3>
  <div class="form-group">
    <label class="form-label">{{ __('Platform commission (%)') }}</label>
    <input type="number" name="commission_rate" step="0.5" min="0" max="50" class="form-control" style="max-width:200px" value="{{ old('commission_rate', $settings['commission_rate']) }}" required>
    <small style="color:var(--gray-700)">{{ __('Applied to new payments when you confirm them. Past subscriptions keep their rate.') }}</small>
  </div>

  <div class="form-group">
    <label class="form-label">{{ __('Hold period before paying coaches (days)') }}</label>
    <input type="number" name="payout_hold_days" step="1" min="0" max="60" class="form-control" style="max-width:200px" value="{{ old('payout_hold_days', $settings['payout_hold_days']) }}" required>
    <small style="color:var(--gray-700)">{{ __('A payment becomes payable to the coach after the coach sends the plan and this many days pass since you confirmed it. This leaves time to refund a trainee if something goes wrong. 0 = right after the plan is sent.') }}</small>
  </div>

  <h3 style="margin:24px 0 16px"><i class="fas fa-wallet"></i> {{ __('Sham Cash account (shown to trainees when they pay)') }}</h3>
  <div class="form-group">
    <label class="form-label">{{ __('Account name') }}</label>
    <input type="text" name="shamcash_name" class="form-control" value="{{ old('shamcash_name', $settings['shamcash_name']) }}">
  </div>
  <div class="form-group">
    <label class="form-label">{{ __('Sham Cash account number / code') }}</label>
    <input type="text" name="shamcash_account" class="form-control" dir="ltr" value="{{ old('shamcash_account', $settings['shamcash_account']) }}">
  </div>
  <div class="form-group">
    <label class="form-label">{{ __('Sham Cash QR code (optional)') }}</label>
    <input type="file" name="shamcash_qr_file" class="form-control" accept="image/*">
    @if($settings['shamcash_qr'])
      <div style="margin-top:8px">
        <img src="{{ asset($settings['shamcash_qr']) }}" alt="" style="max-width:160px;border-radius:8px">
        <label style="display:block;margin-top:6px"><input type="checkbox" name="remove_qr" value="1"> {{ __('Remove image') }}</label>
      </div>
    @endif
  </div>
  <div class="form-group">
    <label class="form-label">{{ __('Extra payment instructions (optional)') }}</label>
    <textarea name="payment_instructions" rows="3" class="form-control">{{ old('payment_instructions', $settings['payment_instructions']) }}</textarea>
  </div>
  <div class="form-group">
    <label class="form-label">{{ __('Support WhatsApp number (optional)') }}</label>
    <input type="text" name="support_whatsapp" class="form-control" dir="ltr" value="{{ old('support_whatsapp', $settings['support_whatsapp']) }}">
  </div>

  <div class="form-actions">
    <button class="btn btn-primary"><i class="fas fa-save"></i> {{ __('Save') }}</button>
  </div>
</form>
@endsection
