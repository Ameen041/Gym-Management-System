<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ __('Trainee Dashboard | Ameen Gym') }}</title>
    @include('partials.head')

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/trainee-dashboard.css') }}">
</head>
<body>

<div class="page">

  {{-- Alerts --}}
  <div class="alerts">
    @if(session('error'))
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ session('error') }}</span>
      </div>
    @endif

    @if(session('success'))
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
      </div>
    @endif
  </div>

  {{-- Header --}}
  <header class="topbar">
    <div class="topbar-left">
      <div class="brand">
        <div class="brand-icon"><i class="fas fa-dumbbell"></i></div>
        <div class="brand-text">
          <h1>{{ __('Trainee Dashboard') }}</h1>
          <p>{{ __('Manage your profile, payments, and plans') }}</p>
        </div>
      </div>
    </div>

    <div class="topbar-right">
      <button type="button" class="btn btn-primary" data-install-app hidden>
        <i class="fas fa-download"></i>
        <span>{{ __('Install App') }}</span>
      </button>

      <a href="{{ route('exercises.index') }}" class="btn btn-ghost">
        <i class="fas fa-dumbbell"></i>
        <span>{{ __('Exercises') }}</span>
      </a>

      @include('partials.lang-switch')

      <button class="btn btn-ghost" data-toggle="#profileCard">
        <i class="fas fa-user"></i>
        <span>{{ __('Profile') }}</span>
      </button>

      <button class="btn btn-ghost" data-toggle="#editProfileCard">
        <i class="fas fa-user-edit"></i>
        <span>{{ __('Edit') }}</span>
      </button>

      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger">
          <i class="fas fa-sign-out-alt"></i>
          <span>{{ __('Logout') }}</span>
        </button>
      </form>
    </div>
  </header>

  {{-- Quick stats --}}
  @php
    $user = Auth::user();
    $photo = $user->profile_photo ? asset('storage/' . $user->profile_photo) : 'https://via.placeholder.com/100';
    $hasActiveSub = isset($activePayment) && $activePayment;
  @endphp

  <section class="stats">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-id-badge"></i></div>
      <div class="stat-meta">
        <div class="stat-label">{{ __('Welcome') }}</div>
        <div class="stat-value">{{ $user->name }}</div>
      </div>
    </div>

    <div class="stat-card {{ $hasActiveSub ? 'ok' : 'warn' }}">
      <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
      <div class="stat-meta">
        <div class="stat-label">{{ __('Subscription') }}</div>
        <div class="stat-value">
          @if($hasActiveSub)
            {{ __('Active') }}
          @else
            {{ __('Not Active') }}
          @endif
        </div>
        <div class="stat-sub">
          @if($hasActiveSub)
            {{ __('Until :date', ['date' => \Carbon\Carbon::parse($activePayment->period_end)->format('Y-m-d')]) }}
          @else
            {{ __('Payment required') }}
          @endif
        </div>
      </div>
    </div>

    <div class="stat-card {{ ($workoutPlan || $nutritionPlan) ? 'ok' : 'warn' }}">
      <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
      <div class="stat-meta">
        <div class="stat-label">{{ __('Plans') }}</div>
        <div class="stat-value">{{ ($workoutPlan || $nutritionPlan) ? __('Available') : __('None') }}</div>
        <div class="stat-sub">{{ __('Workout / Nutrition') }}</div>
      </div>
    </div>
  </section>

  {{-- Main grid --}}
  <main class="grid">

    {{-- Profile card --}}
    <section id="profileCard" class="card">
      <div class="card-head">
        <div class="card-title">
          <i class="fas fa-user"></i>
          <h2>{{ __('Your Profile') }}</h2>
        </div>
        <button class="icon-btn" data-toggle="#profileCard" title="{{ __('Close') }}">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="profile-row">
        <img src="{{ $photo }}" alt="{{ __('Profile photo') }}" class="avatar">
        <div class="profile-main">
          <h3>{{ $user->name }}</h3>
          <p class="muted">{{ $user->email }}</p>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">{{ __('Age') }}</span>
          <span class="info-value">{{ $user->age ?? '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">{{ __('Height (cm)') }}</span>
          <span class="info-value">{{ $user->height ?? '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">{{ __('Weight (kg)') }}</span>
          <span class="info-value">{{ $user->weight ?? '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">{{ __('Status') }}</span>
          <span class="info-value {{ $hasActiveSub ? 'text-ok' : 'text-warn' }}">
            {{ $hasActiveSub ? __('Paid') : __('Not Paid') }}
          </span>
        </div>
      </div>
    </section>

    {{-- Edit profile card --}}
    <section id="editProfileCard" class="card hidden">
      <div class="card-head">
        <div class="card-title">
          <i class="fas fa-user-edit"></i>
          <h2>{{ __('Edit Profile') }}</h2>
        </div>
        <button class="icon-btn" data-toggle="#editProfileCard" title="{{ __('Close') }}">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="{{ route('trainee.profile.update') }}" enctype="multipart/form-data" class="form">
        @csrf

        <div class="form-row">
          <div class="form-group">
            <label>{{ __('Name') }}</label>
            <input class="form-control" type="text" name="name" value="{{ $user->name }}" required>
          </div>

          <div class="form-group">
            <label>{{ __('Age') }}</label>
            <input class="form-control" type="number" name="age" value="{{ $user->age }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>{{ __('Height (cm)') }}</label>
            <input class="form-control" type="number" name="height" value="{{ $user->height }}">
          </div>

          <div class="form-group">
            <label>{{ __('Weight (kg)') }}</label>
            <input class="form-control" type="number" name="weight" value="{{ $user->weight }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>{{ __('Profile Photo') }}</label>
            <input class="form-control" type="file" name="photo" accept="image/*">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            <span>{{ __('Save') }}</span>
          </button>
          <button type="button" class="btn btn-ghost" data-toggle="#editProfileCard">
            <i class="fas fa-times"></i>
            <span>{{ __('Cancel') }}</span>
          </button>
        </div>
      </form>
    </section>

    {{-- Payments / subscription card --}}
    <section class="card">
      <div class="card-head">
        <div class="card-title">
          <i class="fas fa-credit-card"></i>
          <h2>{{ __('Subscription & Payments') }}</h2>
        </div>
      </div>

      @if($hasActiveSub)
        <div class="pill pill-ok">
          <i class="fas fa-check-circle"></i>
          <span>{{ __('Active subscription until') }} <b>{{ \Carbon\Carbon::parse($activePayment->period_end)->format('Y-m-d') }}</b></span>
        </div>
      @else
        <div class="pill pill-warn">
          <i class="fas fa-exclamation-triangle"></i>
          <span><b>{{ __('No active subscription') }}</b> {{ __('— you can’t request plans until admin adds a payment.') }}</span>
        </div>
      @endif

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>{{ __('Date') }}</th>
              <th>{{ __('Amount') }}</th>
              <th>{{ __('Method') }}</th>
              <th>{{ __('Period') }}</th>
            </tr>
          </thead>
          <tbody>
            @if(isset($payments) && $payments->count())
              @foreach($payments->take(5) as $p)
                <tr>
                  <td>{{ \Carbon\Carbon::parse($p->paid_at)->format('Y-m-d') }}</td>
                  <td><b>{{ $p->amount }}</b></td>
                  <td>{{ $p->method ?? '-' }}</td>
                  <td>
                    {{ \Carbon\Carbon::parse($p->period_start)->format('Y-m-d') }}
                    →
                    {{ \Carbon\Carbon::parse($p->period_end)->format('Y-m-d') }}
                  </td>
                </tr>
              @endforeach
            @else
              <tr>
                <td colspan="4" class="table-empty">{{ __('No payments recorded yet.') }}</td>
              </tr>
            @endif
          </tbody>
        </table>

        @if(isset($payments) && $payments->count() > 5)
          <div class="muted small" style="margin-top:10px;">{{ __('Showing latest 5 payments.') }}</div>
        @endif
      </div>
    </section>

    {{-- Plans card --}}
    <section class="card">
      <div class="card-head">
        <div class="card-title">
          <i class="fas fa-clipboard-list"></i>
          <h2>{{ __('Your Plans') }}</h2>
        </div>
      </div>

      <div class="plans">
        {{-- Workout --}}
        <div class="plan-box">
          <div class="plan-head">
            <i class="fas fa-dumbbell"></i>
            <div>
              <h3>{{ __('Workout Plan') }}</h3>
              <p class="muted">{{ __('Training schedule assigned by trainer') }}</p>
            </div>
          </div>

          @if($workoutPlan)
            <a href="{{ route('trainee.schedule') }}" class="btn btn-primary w-100">
              <i class="fas fa-eye"></i><span>{{ __('View Workout Plan') }}</span>
            </a>
          @else
            <a href="{{ route('trainers', ['type' => 'workout']) }}" class="btn btn-ghost w-100">
              <i class="fas fa-plus"></i><span>{{ __('Request Workout Plan') }}</span>
            </a>
          @endif
        </div>

        {{-- Nutrition --}}
        <div class="plan-box">
          <div class="plan-head">
            <i class="fas fa-utensils"></i>
            <div>
              <h3>{{ __('Nutrition Plan') }}</h3>
              <p class="muted">{{ __('Diet plan assigned by trainer') }}</p>
            </div>
          </div>

          @if($nutritionPlan)
            <a href="{{ route('trainee.diet') }}" class="btn btn-primary w-100">
              <i class="fas fa-eye"></i><span>{{ __('View Nutrition Plan') }}</span>
            </a>
          @else
            <a href="{{ route('trainers', ['type' => 'nutrition']) }}" class="btn btn-ghost w-100">
              <i class="fas fa-plus"></i><span>{{ __('Request Nutrition Plan') }}</span>
            </a>
          @endif
        </div>
      </div>

      <div class="note">
        <i class="fas fa-info-circle"></i>
        <span>
          @if($hasActiveSub)
            {{ __('You can request plans normally.') }}
          @else
            {{ __('Requests are disabled until admin adds a payment.') }}
          @endif
        </span>
      </div>
    </section>

  </main>
</div>

<script src="{{ asset('js/trainee-dashboard.js') }}"></script>
</body>
</html>