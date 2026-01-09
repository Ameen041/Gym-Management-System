<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard</title>

    <link rel="stylesheet" href="{{ asset('css/trainer-dashboard.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
</head>

<body>
@php
    use App\Models\User;

    $trainer = auth()->user(); 
    $trainerPhoto = ($trainer && $trainer->profile_photo)
        ? asset('storage/' . $trainer->profile_photo)
        : 'https://via.placeholder.com/100';

    $selectedType = request()->query('type'); // workout | nutrition
    $requestId    = request()->query('request_id');

    $traineeId = request()->query('user_id');
    $trainee   = $traineeId ? User::find($traineeId) : null;

    $totalPending = 0;
    if(isset($pendingRequests) && isset($pendingNutritionRequests)){
        $totalPending = $pendingRequests->count() + $pendingNutritionRequests->count();
    }

    $planLabel = $selectedType === 'workout'
        ? 'Workout Plan'
        : ($selectedType === 'nutrition' ? 'Nutrition Plan' : null);

    $weekDays = ['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'];
@endphp

<div class="page">

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:12px;">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom:12px;">
            <i class="fas fa-exclamation-triangle"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Topbar --}}
    <div class="topbar">
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-dumbbell"></i></div>
            <div class="brand-text">
                <h1>Trainer Dashboard</h1>
                <p>Everything you need to manage trainee plans</p>
            </div>
        </div>

        <div class="topbar-right">
            {{-- Requests Bell --}}
            <a href="{{ route('trainer.requests') }}" class="notification-btn" title="Requests">
                <i class="fas fa-bell"></i>
                @if($totalPending > 0)
                    <span class="notification-badge">{{ $totalPending }}</span>
                @endif
            </a>

            <button type="button" class="btn btn-ghost" id="toggleProfileBtn">
                <i class="fas fa-user"></i> Profile
            </button>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    {{-- Welcome --}}
    <div class="welcome">
        <div class="welcome-left">
            <img src="{{ $trainerPhoto }}" class="avatar" alt="Trainer">
            <div>
                <h2>Welcome back, {{ $trainer?->name ?? 'Trainer' }} 👋</h2>
                <p>
                    You have <b>{{ $totalPending }}</b> pending request(s).
                    @if($trainee)
                        You are creating a
                        @if($planLabel)
                            <b>{{ $planLabel }}</b>
                        @else
                            plan
                        @endif
                        for <b>{{ $trainee->name }}</b>.
                    @else
                        Select a trainee from the Requests page (bell icon) to start.
                    @endif
                </p>
            </div>
        </div>

        <div class="welcome-actions">
            @if(!$trainee)
                <div class="hint-pill">
                    <i class="fas fa-lightbulb"></i>
                    Tip: Click the bell to pick a trainee.
                </div>
            @else
                <div class="hint-pill">
                    <i class="fas fa-bolt"></i>
                    Ready to build
                    @if($planLabel)
                        <b>{{ $planLabel }}</b>
                    @else
                        a plan
                    @endif
                    for <b>{{ $trainee->name }}</b>
                </div>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dumbbell"></i></div>
            <div>
                <div class="stat-label">Workout Requests</div>
                <div class="stat-value">{{ isset($pendingRequests) ? $pendingRequests->count() : 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-utensils"></i></div>
            <div>
                <div class="stat-label">Nutrition Requests</div>
                <div class="stat-value">{{ isset($pendingNutritionRequests) ? $pendingNutritionRequests->count() : 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-label">Current Trainee</div>
                <div class="stat-value">{{ $trainee?->name ?? 'None' }}</div>
            </div>
        </div>
    </div>

    {{-- Profile box --}}
    <div id="profileBox" class="card hidden">
        <div class="card-head">
            <div class="card-title">
                <i class="fas fa-id-card"></i>
                <h3>Your Profile</h3>
            </div>
            <button class="btn" type="button" id="closeProfileBtn">
                <i class="fas fa-times"></i> Close
            </button>
        </div>

        <div class="profile-box">
            <div class="welcome-left" style="margin-bottom:12px;">
                <img src="{{ $trainerPhoto }}" class="avatar" alt="Trainer">
                <div>
                    <h2 style="font-size:16px;margin:0">{{ $trainer?->name ?? 'Trainer' }}</h2>
                    <p class="muted small">{{ $trainer?->email ?? '' }}</p>
                </div>
            </div>

            <div class="profile-grid">
                <div class="info-item">
                    <span class="info-label">Age</span>
                    <span class="info-value">{{ $trainer?->age ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Height</span>
                    <span class="info-value">{{ $trainer?->height ? $trainer->height.' cm' : '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Weight</span>
                    <span class="info-value">{{ $trainer?->weight ? $trainer->weight.' kg' : '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Role</span>
                    <span class="info-value">Trainer</span>
                </div>
            </div>

            <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn btn-ghost" id="toggleEditBtn">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </button>
            </div>

            <form id="editBox" class="form hidden"
                  method="POST"
                  action="{{ route('trainer.profile.update') }}"
                  enctype="multipart/form-data"
                  style="margin-top:12px;">
                @csrf

                <div class="grid two-col">
                    <div class="form-group">
                        <label>Name</label>
                        <input class="form-control" type="text" name="name" value="{{ $trainer?->name ?? '' }}" required>
                    </div>
                    <div class="form-group">
                        <label>Age</label>
                        <input class="form-control" type="number" name="age" value="{{ $trainer?->age ?? '' }}">
                    </div>
                </div>

                <div class="grid two-col">
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input class="form-control" type="number" name="height" value="{{ $trainer?->height ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input class="form-control" type="number" name="weight" value="{{ $trainer?->weight ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Profile Photo</label>
                    <input class="form-control" type="file" name="photo" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    {{-- Plans --}}
    @if($trainee)

        @if(!$selectedType)
            <div class="card" style="margin-top:14px;">
                <b>⚠️ Please open the plan form from the Requests page.</b>
            </div>
        @endif

        <div class="plans-stack" style="margin-top:14px;">

            {{-- Workout Weekly --}}
            @if($selectedType === 'workout')
                <div class="card plan-card">
                    <div class="card-head">
                        <div class="card-title">
                            <i class="fas fa-running"></i>
                            <h3>Add Workout Plan</h3>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('trainer.addWorkout') }}" class="form">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $trainee->id }}">
                        <input type="hidden" name="request_id" value="{{ $requestId }}">

                        <div class="form-group">
                            <label>Plan Title</label>
                            <input type="text" name="title" required class="form-control" placeholder="e.g. Strength Plan - Week 1">
                        </div>

                        @if(isset($workoutTemplates) && $workoutTemplates->count())
                            <div class="form-group">
                                <label>Load Workout Template</label>
                                <select class="form-control" onchange="loadWorkoutTemplateWeekly(this)">
                                    <option value="">-- Select Template --</option>
                                    @foreach($workoutTemplates as $tpl)
                                        <option value="{{ $tpl->id }}" data-body="{{ trim($tpl->plan_details) }}">
                                            {{ $tpl->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="weekly-wrap">
                            @foreach($weekDays as $day)
                                <div class="day-card">
                                    <div class="day-head">
                                        <div class="day-title">
                                            <i class="fas fa-calendar-day"></i>
                                            <h4>{{ $day }}</h4>
                                        </div>

                                        <button type="button" class="add-mini-btn" data-type="workout" data-day="{{ $day }}">
                                            <i class="fas fa-plus"></i> Add Exercise
                                        </button>
                                    </div>

                                    <div class="rows-wrap rows-day" id="workout-rows-{{ $day }}">
                                        <div class="workout-row">
                                            <input type="hidden" name="day[]" value="{{ $day }}">

                                            <select name="muscle[]" required>
                                                <option value="">Muscle</option>
                                                @foreach(['Chest','Back','Shoulders','Legs','Abs','Biceps','Triceps'] as $muscle)
                                                    <option value="{{ $muscle }}">{{ $muscle }}</option>
                                                @endforeach
                                            </select>

                                            <input type="text" name="exercise[]" placeholder="Exercise (Full name)" required>
                                            <input type="number" name="sets[]" placeholder="Sets" required>
                                            <input type="text" name="reps[]" placeholder="Reps (e.g. 10-12)" required>

                                            <button type="button" class="remove-row" onclick="removeWorkoutRow(this)" title="Remove Row">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Submit Workout Plan
                        </button>
                    </form>
                </div>
            @endif

            {{-- Nutrition Weekly --}}
            @if($selectedType === 'nutrition')
                <div class="card plan-card">
                    <div class="card-head">
                        <div class="card-title">
                            <i class="fas fa-utensils"></i>
                            <h3>Add Nutrition Plan</h3>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('trainer.addNutrition') }}" class="form">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $trainee->id }}">
                        <input type="hidden" name="request_id" value="{{ $requestId }}">

                        <div class="form-group">
                            <label>Plan Title</label>
                            <input type="text" name="title" required class="form-control" placeholder="e.g. Cutting Plan - Week 1">
                        </div>

                        @if(isset($nutritionTemplates) && $nutritionTemplates->count())
                            <div class="form-group">
                                <label>Load Nutrition Template</label>
                                <select class="form-control" onchange="loadNutritionTemplateWeekly(this)">
                                    <option value="">-- Select Template --</option>
                                    @foreach($nutritionTemplates as $tpl)
                                        <option value="{{ $tpl->id }}" data-body="{{ trim($tpl->plan_details) }}">
                                            {{ $tpl->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="weekly-wrap">
                            @foreach($weekDays as $day)
                                <div class="day-card">
                                    <div class="day-head">
                                        <div class="day-title">
                                            <i class="fas fa-calendar-day"></i>
                                            <h4>{{ $day }}</h4>
                                        </div>

                                        <button type="button" class="add-mini-btn" data-type="nutrition" data-day="{{ $day }}">
                                            <i class="fas fa-plus"></i> Add Meal
                                        </button>
                                    </div>

                                    <div class="rows-wrap rows-day" id="nutrition-rows-{{ $day }}">
                                        <div class="nutrition-row">
                                            <input type="hidden" name="day[]" value="{{ $day }}">

                                            <input type="number" name="meal_number[]" placeholder="Meal #" min="1" required>
                                            <input type="text" name="description[]" placeholder="Meal Description (Full text)" required>
                                            <input type="number" name="calories[]" placeholder="Calories" required>
                                            <input type="number" name="protein[]" placeholder="Protein (g)" step="0.1" required>
                                            <input type="number" name="carbs[]" placeholder="Carbs (g)" step="0.1" required>
                                            <input type="number" name="fat[]" placeholder="Fat (g)" step="0.1" required>

                                            <button type="button" class="remove-row" onclick="removeNutritionRow(this)" title="Remove Row">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Submit Nutrition Plan
                        </button>
                    </form>
                </div>
            @endif

        </div>
    @endif

</div>

<script src="{{ asset('js/trainer-dashboard.js') }}"></script>
</body>
</html>