@extends('layouts.panel')
@section('title', __('Subscribe'))

@section('content')
@php
  use App\Http\Controllers\SubscriptionController as Sub;
  use App\Support\Money;
  $old = fn ($key, $fallback = null) => old($key, $lastIntake[$key] ?? $fallback);
@endphp

<div class="page-title">
  <h1>{{ __('Subscribe with :name', ['name' => $coach->name]) }}</h1>
  <p>{{ __('Step 1 of 2') }}</p>
</div>

<div class="grid-2" style="align-items:start">
  <form class="panel" method="POST" action="{{ route('subscriptions.store', [$coach, $package]) }}" enctype="multipart/form-data">
    @csrf
    <div class="panel-head"><h2><i class="fas fa-clipboard-list"></i> {{ __('Your goal') }}</h2></div>

    <div class="form-row">
      <div class="form-group">
        <label>{{ __('Main goal') }}</label>
        <select name="goal" required>
          @foreach(Sub::GOALS as $key => $label)
            <option value="{{ $key }}" @selected($old('goal') === $key)>{{ __($label) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group">
        <label>{{ __('Training experience') }}</label>
        <select name="level" required>
          @foreach(Sub::LEVELS as $key => $label)
            <option value="{{ $key }}" @selected($old('level') === $key)>{{ __($label) }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>{{ __('Tell the coach more about your goal (optional)') }}</label>
      <textarea name="goal_details" rows="3">{{ $old('goal_details') }}</textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>{{ __('Training days per week') }}</label>
        <select name="days_per_week" required>
          @for($d = 2; $d <= 7; $d++)
            <option value="{{ $d }}" @selected((int) $old('days_per_week', 4) === $d)>{{ $d }}</option>
          @endfor
        </select>
      </div>
      <div class="form-group">
        <label>{{ __('Your gym (optional)') }}</label>
        <input type="text" name="gym" value="{{ $old('gym') }}">
      </div>
    </div>

    <div class="form-group">
      <label>{{ __('Injuries or health notes (optional)') }}</label>
      <textarea name="health_notes" rows="2">{{ $old('health_notes') }}</textarea>
    </div>

    <div class="panel-head" style="margin-top:8px"><h2><i class="fas fa-user"></i> {{ __('About you') }}</h2></div>
    <div class="form-row">
      <div class="form-group">
        <label>{{ __('Age') }}</label>
        <input type="number" name="age" min="10" max="100" value="{{ old('age', $user->age) }}" required>
      </div>
      <div class="form-group">
        <label>{{ __('Height (cm)') }}</label>
        <input type="number" name="height" min="100" max="250" value="{{ old('height', $user->height) }}" required>
      </div>
      <div class="form-group">
        <label>{{ __('Weight (kg)') }}</label>
        <input type="number" name="weight" min="30" max="300" step="0.1" value="{{ old('weight', $user->weight) }}" required>
      </div>
    </div>

    <div class="form-group">
      <label>{{ __('Phone number') }}</label>
      <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09xxxxxxxx" dir="ltr" required>
      <div class="form-hint">{{ __('Only the Himma team sees it, for support. You talk to your coach in the Himma chat.') }}</div>
    </div>

    <div class="form-group">
      <label>{{ __('Body photos (optional, up to 4)') }}</label>
      <input type="file" name="body_photos[]" accept="image/*" multiple>
      <div class="form-hint">{{ __('Only your coach can see them. They help to build a better plan.') }}</div>
    </div>

    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-arrow-right"></i> {{ __('Continue to payment') }}</button>
  </form>

  <aside class="panel">
    <div class="profile-head" style="margin-bottom:14px">
      <img src="{{ $coach->photo_url }}" alt="" class="avatar-sm">
      <div>
        <strong>{{ $coach->name }}</strong>
        <div class="muted small">{{ $package->title }}</div>
      </div>
    </div>
    <div class="package">
      <span class="price">{{ Money::format($package->price) }} <small>/ {{ __(':days days', ['days' => $package->duration_days]) }}</small></span>
      <div class="muted small"><i class="fas fa-check-circle" style="color:var(--success)"></i> {{ $package->includes_label }}</div>
    </div>
  </aside>
</div>
@endsection
