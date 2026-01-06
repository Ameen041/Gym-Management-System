<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainers</title>

    <link rel="stylesheet" href="{{ asset('css/trainers.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
@php
    $type = request('type') === 'nutrition' ? 'nutrition' : 'workout';
    $planLabel = $type === 'nutrition' ? 'Nutrition Plan' : 'Workout Plan';
@endphp

<div class="page">

    <div class="topbar">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-dumbbell"></i></div>
            <div class="brand-text">
                <h1>Trainers</h1>
                <p>Select a trainer to request your {{ $planLabel }}</p>
            </div>
        </div>

        <div class="topbar-right">
            <a href="{{ url()->previous() }}" class="btn btn-ghost">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            @auth
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            @endauth
        </div>
    </div>

    <div class="hero">
        <div class="hero-left">
            <div class="hero-icon"><i class="fas fa-users"></i></div>
            <div>
                <h2>Choose your trainer 👋</h2>
                <p>Pick one trainer to start your {{ strtolower($planLabel) }} request.</p>
            </div>
        </div>
        <div class="hero-pill">
            <i class="fas fa-bolt"></i>
            Type: <b>{{ $planLabel }}</b>
        </div>
    </div>

    <div class="trainers-grid">
        @forelse ($trainers as $trainer)
            @php
                $photo = $trainer->profile_photo
                    ? asset('storage/' . $trainer->profile_photo)
                    : 'https://via.placeholder.com/900x900?text=Trainer';
            @endphp

            <div class="trainer-card">

                <div class="trainer-photo">
                    <img src="{{ $photo }}" alt="Trainer Photo">
                </div>

                <div class="trainer-body">
                    <div class="trainer-title">
                        <h3>{{ $trainer->name }}</h3>
                        <p class="trainer-email">{{ $trainer->email }}</p>
                    </div>

                    <div class="trainer-meta">
                        @if(!is_null($trainer->age))
                            <span><i class="fas fa-birthday-cake"></i> {{ $trainer->age }} yrs</span>
                        @endif
                        @if(!is_null($trainer->height))
                            <span><i class="fas fa-ruler-vertical"></i> {{ $trainer->height }} cm</span>
                        @endif
                        @if(!is_null($trainer->weight))
                            <span><i class="fas fa-weight"></i> {{ $trainer->weight }} kg</span>
                        @endif
                    </div>

                    <div class="trainer-actions">
                        @auth
                            @if(auth()->user()->role === 'trainee')
                                <a class="btn btn-primary w-100"
                                   href="{{ $type === 'nutrition'
                                        ? route('trainee.diet', ['trainer_id' => $trainer->id])
                                        : route('trainee.schedule', ['trainer_id' => $trainer->id]) }}">
                                    <i class="fas fa-check"></i> Select This Trainer
                                </a>
                            @else
                                <button class="btn btn-ghost w-100" type="button"
                                        onclick="alert('Please login as a trainee to select a trainer.')">
                                    <i class="fas fa-lock"></i> Select This Trainer
                                </button>
                            @endif
                        @else
                            <button class="btn btn-ghost w-100" type="button"
                                    onclick="alert('Please login as a trainee to select a trainer.')">
                                <i class="fas fa-lock"></i> Select This Trainer
                            </button>
                        @endauth
                    </div>
                </div>

            </div>

        @empty
            <div class="empty-card">
                <i class="fas fa-users-slash"></i>
                <h3>No trainers found</h3>
                <p>Please add trainers first.</p>
            </div>
        @endforelse
    </div>

</div>

</body>
</html>