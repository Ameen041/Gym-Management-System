@extends('layouts.panel')
@section('title', __('My Profile'))

@section('content')
@php use App\Models\CoachProfile; @endphp

<div class="page-title">
  <h1>{{ __('My Profile') }}</h1>
  <p>{{ __('This is what trainees see on the coaches page. A complete profile gets more clients.') }}</p>
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
      <label>{{ __('WhatsApp number') }}</label>
      <input type="tel" name="phone" value="{{ old('phone', $coach->phone) }}" placeholder="09xxxxxxxx" required>
      <div class="form-hint">{{ __('Shown only to your clients after they pay.') }}</div>
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
@endsection
