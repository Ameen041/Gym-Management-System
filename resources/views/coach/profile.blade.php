@extends('layouts.panel')
@section('title', __('My Profile'))

@section('content')
@php use App\Models\CoachProfile; @endphp

<div class="page-title">
  <h1>{{ __('My Profile') }}</h1>
  <p>{{ __('What trainees see on the coaches page.') }}</p>
</div>

<form class="panel" method="POST" action="{{ route('coach.profile.update') }}" enctype="multipart/form-data">
  @csrf
  <div class="profile-head" style="margin-bottom:16px">
    <img src="{{ $coach->photo_url }}" alt="" class="avatar-lg">
    <div class="form-group" style="flex:1;min-width:220px;margin:0">
      <label>{{ __('Profile Photo') }}</label>
      <input type="file" name="photo" accept="image/*">
      <div class="form-hint">{{ __('A clear photo of your face builds trust.') }}</div>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>{{ __('Name') }}</label>
      <input type="text" name="name" value="{{ old('name', $coach->name) }}" required>
    </div>
    <div class="form-group">
      <label>{{ __('Phone number') }}</label>
      <input type="tel" name="phone" value="{{ old('phone', $coach->phone) }}" placeholder="09xxxxxxxx" dir="ltr" required>
      <div class="form-hint">{{ __('Only the Himma team sees it, to reach you about payouts. Clients talk to you in the chat.') }}</div>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>{{ __('Years of experience') }}</label>
      <input type="number" name="experience_years" min="0" max="60" value="{{ old('experience_years', $profile->experience_years) }}">
    </div>
    <div class="form-group">
      <label>{{ __('City') }}</label>
      <select name="city">
        <option value="">—</option>
        @foreach(CoachProfile::CITIES as $key => $label)
          <option value="{{ $key }}" @selected(old('city', $profile->city) === $key)>{{ __($label) }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <label>{{ __('Gender') }}</label>
      <select name="gender">
        <option value="">—</option>
        <option value="male" @selected(old('gender', $profile->gender) === 'male')>{{ __('Male') }}</option>
        <option value="female" @selected(old('gender', $profile->gender) === 'female')>{{ __('Female') }}</option>
      </select>
    </div>
  </div>

  <div class="form-group">
    <label>{{ __('Specialties') }}</label>
    <div class="check-grid">
      @foreach(CoachProfile::SPECIALTIES as $key => $label)
        <label class="check">
          <input type="checkbox" name="specialties[]" value="{{ $key }}" @checked(in_array($key, old('specialties', (array) $profile->specialties), true))>
          {{ __($label) }}
        </label>
      @endforeach
    </div>
  </div>

  <div class="form-group">
    <label>{{ __('About you') }}</label>
    <textarea name="bio" rows="6" required minlength="30" placeholder="{{ __('Your experience, how you work with clients, results you achieved...') }}">{{ old('bio', $profile->bio) }}</textarea>
  </div>

  <div class="form-group">
    <label>{{ __('Certificates (images)') }}</label>
    @if(!empty($profile->certificates))
      <div class="thumbs" style="margin-bottom:8px">
        @foreach($profile->certificates as $path)
          <label style="text-align:center">
            <img src="{{ asset('storage/' . $path) }}" alt="">
            <div class="small"><input type="checkbox" name="remove_certificates[]" value="{{ $path }}"> {{ __('Remove') }}</div>
          </label>
        @endforeach
      </div>
    @endif
    <input type="file" name="certificates[]" accept="image/*" multiple>
    <div class="form-hint">{{ __('Certificates are checked by our team before your profile is approved.') }}</div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('Save Changes') }}</button>
    <a href="{{ route('coaches.show', $coach) }}" class="btn btn-light" target="_blank"><i class="fas fa-eye"></i> {{ __('Preview') }}</a>
  </div>
</form>

{{-- Private: where the platform sends the coach's earnings --}}
<form class="panel" id="payout" method="POST" action="{{ route('coach.profile.payout') }}" enctype="multipart/form-data">
  @csrf
  <div class="panel-head">
    <h2><i class="fas fa-wallet"></i> {{ __('Your Sham Cash account') }}</h2>
    <span class="muted small"><i class="fas fa-lock"></i> {{ __('Only the platform admin sees it') }}</span>
  </div>
  <p class="muted small" style="margin-bottom:14px">{{ __('Your earnings are sent here every week.') }}</p>

  @if($errors->payout->any())
    <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><div>{{ $errors->payout->first() }}</div></div>
  @endif

  <div class="form-row">
    <div class="form-group">
      <label for="shamcash-name">{{ __('Account holder name') }}</label>
      <input type="text" id="shamcash-name" name="shamcash_name" maxlength="100" required value="{{ old('shamcash_name', $profile->shamcash_name) }}">
    </div>
    <div class="form-group">
      <label for="shamcash-account">{{ __('Sham Cash account number / code') }}</label>
      <input type="text" id="shamcash-account" name="shamcash_account" maxlength="100" required dir="ltr" value="{{ old('shamcash_account', $profile->shamcash_account) }}">
    </div>
  </div>

  <div class="form-group">
    <label for="shamcash-qr">{{ __('Account QR code') }} <span class="muted small">{{ __('optional') }}</span></label>
    @if($profile->shamcash_qr)
      <div class="profile-head" style="margin-bottom:8px">
        <img src="{{ route('coaches.payout-qr', $coach) }}" alt="{{ __('Account QR code') }}" style="width:96px;height:96px;object-fit:contain;border-radius:12px;border:1px solid var(--line);background:#fff">
        <label class="muted small"><input type="checkbox" name="remove_qr" value="1"> {{ __('Remove image') }}</label>
      </div>
    @endif
    <input type="file" id="shamcash-qr" name="shamcash_qr" accept="image/*">
  </div>

  @if($profile->hasPayoutAccount())
    <div class="form-group">
      <label for="payout-password">{{ __('Your password') }}</label>
      <input type="password" id="payout-password" name="current_password" autocomplete="current-password" style="max-width:320px">
      <div class="form-hint">{{ __('Needed to change the account, to protect your money.') }}</div>
    </div>
  @endif

  <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('Save account') }}</button>
</form>
@endsection
