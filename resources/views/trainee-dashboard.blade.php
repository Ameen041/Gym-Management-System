<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trainee Dashboard | Ameen Gym</title>

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
          <h1>Trainee Dashboard</h1>
          <p>Manage your profile, payments, and plans</p>
        </div>
      </div>
    </div>

    <div class="topbar-right">
      <button class="btn btn-ghost" data-toggle="#profileCard">
        <i class="fas fa-user"></i>
        <span>Profile</span>
      </button>

      <button class="btn btn-ghost" data-toggle="#editProfileCard">
        <i class="fas fa-user-edit"></i>
        <span>Edit</span>
      </button>

      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-danger">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
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
        <div class="stat-label">Welcome</div>
        <div class="stat-value">{{ $user->name }}</div>
      </div>
    </div>

    <div class="stat-card {{ $hasActiveSub ? 'ok' : 'warn' }}">
      <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
      <div class="stat-meta">
        <div class="stat-label">Subscription</div>
        <div class="stat-value">
          @if($hasActiveSub)
            Active
          @else
            Not Active
          @endif
        </div>
        <div class="stat-sub">
          @if($hasActiveSub)
            Until {{ \Carbon\Carbon::parse($activePayment->period_end)->format('Y-m-d') }}
          @else
            Payment required
          @endif
        </div>
      </div>
    </div>

    <div class="stat-card {{ ($workoutPlan || $nutritionPlan) ? 'ok' : 'warn' }}">
      <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
      <div class="stat-meta">
        <div class="stat-label">Plans</div>
        <div class="stat-value">{{ ($workoutPlan || $nutritionPlan) ? 'Available' : 'None' }}</div>
        <div class="stat-sub">Workout / Nutrition</div>
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
          <h2>Your Profile</h2>
        </div>
        <button class="icon-btn" data-toggle="#profileCard" title="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="profile-row">
        <img src="{{ $photo }}" alt="Profile photo" class="avatar">
        <div class="profile-main">
          <h3>{{ $user->name }}</h3>
          <p class="muted">{{ $user->email }}</p>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Age</span>
          <span class="info-value">{{ $user->age ?? '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Height (cm)</span>
          <span class="info-value">{{ $user->height ?? '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Weight (kg)</span>
          <span class="info-value">{{ $user->weight ?? '—' }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Status</span>
          <span class="info-value {{ $hasActiveSub ? 'text-ok' : 'text-warn' }}">
            {{ $hasActiveSub ? 'Paid' : 'Not Paid' }}
          </span>
        </div>
      </div>
    </section>

    {{-- Edit profile card --}}
    <section id="editProfileCard" class="card hidden">
      <div class="card-head">
        <div class="card-title">
          <i class="fas fa-user-edit"></i>
          <h2>Edit Profile</h2>
        </div>
        <button class="icon-btn" data-toggle="#editProfileCard" title="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="{{ route('trainee.profile.update') }}" enctype="multipart/form-data" class="form">
        @csrf

        <div class="form-row">
          <div class="form-group">
            <label>Name</label>
            <input class="form-control" type="text" name="name" value="{{ $user->name }}" required>
          </div>

          <div class="form-group">
            <label>Age</label>
            <input class="form-control" type="number" name="age" value="{{ $user->age }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Height (cm)</label>
            <input class="form-control" type="number" name="height" value="{{ $user->height }}">
          </div>

          <div class="form-group">
            <label>Weight (kg)</label>
            <input class="form-control" type="number" name="weight" value="{{ $user->weight }}">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Profile Photo</label>
            <input class="form-control" type="file" name="photo" accept="image/*">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            <span>Save</span>
          </button>
          <button type="button" class="btn btn-ghost" data-toggle="#editProfileCard">
            <i class="fas fa-times"></i>
            <span>Cancel</span>
          </button>
        </div>
      </form>
    </section>

    {{-- Payments / subscription card --}}
    <section class="card">
      <div class="card-head">
        <div class="card-title">
          <i class="fas fa-credit-card"></i>
          <h2>Subscription & Payments</h2>
        </div>
      </div>

      @if($hasActiveSub)
        <div class="pill pill-ok">
          <i class="fas fa-check-circle"></i>
          <span>Active subscription until <b>{{ \Carbon\Carbon::parse($activePayment->period_end)->format('Y-m-d') }}</b></span>
        </div>
      @else
        <div class="pill pill-warn">
          <i class="fas fa-exclamation-triangle"></i>
          <span><b>No active subscription</b> — you can’t request plans until admin adds a payment.</span>
        </div>
      @endif

      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Period</th>
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
                <td colspan="4" class="table-empty">No payments recorded yet.</td>
              </tr>
            @endif
          </tbody>
        </table>

        @if(isset($payments) && $payments->count() > 5)
          <div class="muted small" style="margin-top:10px;">Showing latest 5 payments.</div>
        @endif
      </div>
    </section>

    {{-- Plans card --}}
    <section class="card">
      <div class="card-head">
        <div class="card-title">
          <i class="fas fa-clipboard-list"></i>
          <h2>Your Plans</h2>
        </div>
      </div>

      <div class="plans">
        {{-- Workout --}}
        <div class="plan-box">
          <div class="plan-head">
            <i class="fas fa-dumbbell"></i>
            <div>
              <h3>Workout Plan</h3>
              <p class="muted">Training schedule assigned by trainer</p>
            </div>
          </div>

          @if($workoutPlan)
            <a href="{{ route('trainee.schedule') }}" class="btn btn-primary w-100">
              <i class="fas fa-eye"></i><span>View Workout Plan</span>
            </a>
          @else
            <a href="{{ route('trainers', ['type' => 'workout']) }}" class="btn btn-ghost w-100">
              <i class="fas fa-plus"></i><span>Request Workout Plan</span>
            </a>
          @endif
        </div>

        {{-- Nutrition --}}
        <div class="plan-box">
          <div class="plan-head">
            <i class="fas fa-utensils"></i>
            <div>
              <h3>Nutrition Plan</h3>
              <p class="muted">Diet plan assigned by trainer</p>
            </div>
          </div>

          @if($nutritionPlan)
            <a href="{{ route('trainee.diet') }}" class="btn btn-primary w-100">
              <i class="fas fa-eye"></i><span>View Nutrition Plan</span>
            </a>
          @else
            <a href="{{ route('trainers', ['type' => 'nutrition']) }}" class="btn btn-ghost w-100">
              <i class="fas fa-plus"></i><span>Request Nutrition Plan</span>
            </a>
          @endif
        </div>
      </div>

      <div class="note">
        <i class="fas fa-info-circle"></i>
        <span>
          @if($hasActiveSub)
            You can request plans normally.
          @else
            Requests are disabled until admin adds a payment.
          @endif
        </span>
      </div>
    </section>

  </main>
</div>

<script src="{{ asset('js/trainee-dashboard.js') }}"></script>
</body>
</html>