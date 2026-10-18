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

    <div class="panel-head" style="margin-top:8px" id="health"><h2><i class="fas fa-heart-pulse"></i> {{ __('Your health') }}</h2></div>
    <p class="muted small" style="margin:-6px 0 12px">{{ __('Your coach needs these answers to keep your plan safe. Only your coach and the Himma team see them.') }}</p>
    <div class="health-qs" data-health>
      @foreach(\App\Models\Subscription::HEALTH_QUESTIONS as $key => $question)
        @php($answer = (string) old("health.$key", isset($lastIntake['health'][$key]) ? (int) $lastIntake['health'][$key] : ''))
        <div class="health-q" role="radiogroup" aria-labelledby="hq-{{ $key }}">
          <p class="health-q-text" id="hq-{{ $key }}">{{ __($question) }}</p>
          <div class="yes-no">
            <label><input type="radio" name="health[{{ $key }}]" value="0" required @checked($answer === '0')> <span>{{ __('No') }}</span></label>
            <label><input type="radio" name="health[{{ $key }}]" value="1" @checked($answer === '1')> <span>{{ __('Yes') }}</span></label>
          </div>
        </div>
      @endforeach
    </div>

    <div class="alert alert-warning" data-health-yes hidden>
      <i class="fas fa-user-doctor"></i>
      <div><strong>{{ __('Check with your doctor before you start.') }}</strong> {{ __('Write below what it is and what your doctor said, so your coach builds the plan around it.') }}</div>
    </div>

    <div class="form-group">
      <label>{{ __('Injuries or health notes') }} <span class="muted small" data-health-optional>({{ __('optional') }})</span></label>
      <textarea name="health_notes" rows="2" data-health-notes>{{ $old('health_notes') }}</textarea>
    </div>
    @if($package->includes_nutrition)
      <div class="form-group">
        <label>{{ __('Food allergies or foods you don\'t eat (optional)') }}</label>
        <input type="text" name="food_notes" maxlength="500" value="{{ $old('food_notes') }}" placeholder="{{ __('e.g. lactose, peanuts, no fish') }}">
      </div>
    @endif

    <label class="check" data-health-doctor hidden style="margin-bottom:10px">
      <input type="checkbox" name="doctor" value="1" @checked(old('doctor'))>
      <span>{{ __('I checked with my doctor, or I will before I start training.') }}</span>
    </label>

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

    <label class="check" style="margin-bottom:14px;align-items:flex-start">
      <input type="checkbox" name="consent" value="1" required @checked(old('consent')) style="margin-top:3px">
      <span>{{ __('My answers are true. I know the plan is guidance from a coach, not medical advice, and I will stop and see a doctor if I feel chest pain, dizziness or unusual pain.') }}
        <a href="{{ route('terms') }}#health" target="_blank">{{ __('Health notice') }}</a></span>
    </label>

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
