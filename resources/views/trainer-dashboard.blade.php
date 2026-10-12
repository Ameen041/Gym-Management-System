<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Trainer Dashboard') }}</title>
    @include('partials.head')

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
        ? __('Workout Plan')
        : ($selectedType === 'nutrition' ? __('Nutrition Plan') : null);

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
                <h1>{{ __('Trainer Dashboard') }}</h1>
                <p>{{ __('Everything you need to manage trainee plans') }}</p>
            </div>
        </div>

        <div class="topbar-right">
            {{-- Requests Bell --}}
            <a href="{{ route('trainer.requests') }}" class="notification-btn" title="{{ __('Requests') }}">
                <i class="fas fa-bell"></i>
                @if($totalPending > 0)
                    <span class="notification-badge">{{ $totalPending }}</span>
                @endif
            </a>

            <button type="button" class="btn btn-primary" data-install-app hidden>
                <i class="fas fa-download"></i> {{ __('Install App') }}
            </button>

            <a href="{{ route('exercises.index') }}" class="btn btn-ghost">
                <i class="fas fa-dumbbell"></i> {{ __('Exercises') }}
            </a>

            @include('partials.lang-switch')

            <button type="button" class="btn btn-ghost" id="toggleProfileBtn">
                <i class="fas fa-user"></i> {{ __('Profile') }}
            </button>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Welcome --}}
    <div class="welcome">
        <div class="welcome-left">
            <img src="{{ $trainerPhoto }}" class="avatar" alt="{{ __('Trainer') }}">
            <div>
                <h2>{{ __('Welcome back, :name', ['name' => $trainer?->name ?? __('Trainer')]) }} 👋</h2>
                <p>
                    {{ __('You have') }} <b>{{ $totalPending }}</b> {{ __('pending request(s).') }}
                    @if($trainee)
                        {{ __('You are creating a') }}
                        @if($planLabel)
                            <b>{{ $planLabel }}</b>
                        @else
                            {{ __('plan') }}
                        @endif
                        {{ __('for') }} <b>{{ $trainee->name }}</b>.
                    @else
                        {{ __('Select a trainee from the Requests page (bell icon) to start.') }}
                    @endif
                </p>
            </div>
        </div>

        <div class="welcome-actions">
            @if(!$trainee)
                <div class="hint-pill">
                    <i class="fas fa-lightbulb"></i>
                    {{ __('Tip: Click the bell to pick a trainee.') }}
                </div>
            @else
                <div class="hint-pill">
                    <i class="fas fa-bolt"></i>
                    {{ __('Ready to build') }}
                    @if($planLabel)
                        <b>{{ $planLabel }}</b>
                    @else
                        {{ __('a plan') }}
                    @endif
                    {{ __('for') }} <b>{{ $trainee->name }}</b>
                </div>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dumbbell"></i></div>
            <div>
                <div class="stat-label">{{ __('Workout Requests') }}</div>
                <div class="stat-value">{{ isset($pendingRequests) ? $pendingRequests->count() : 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-utensils"></i></div>
            <div>
                <div class="stat-label">{{ __('Nutrition Requests') }}</div>
                <div class="stat-value">{{ isset($pendingNutritionRequests) ? $pendingNutritionRequests->count() : 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-label">{{ __('Current Trainee') }}</div>
                <div class="stat-value">{{ $trainee?->name ?? __('None') }}</div>
            </div>
        </div>
    </div>

    {{-- Profile box --}}
    <div id="profileBox" class="card hidden">
        <div class="card-head">
            <div class="card-title">
                <i class="fas fa-id-card"></i>
                <h3>{{ __('Your Profile') }}</h3>
            </div>
            <button class="btn" type="button" id="closeProfileBtn">
                <i class="fas fa-times"></i> {{ __('Close') }}
            </button>
        </div>

        <div class="profile-box">
            <div class="welcome-left" style="margin-bottom:12px;">
                <img src="{{ $trainerPhoto }}" class="avatar" alt="{{ __('Trainer') }}">
                <div>
                    <h2 style="font-size:16px;margin:0">{{ $trainer?->name ?? __('Trainer') }}</h2>
                    <p class="muted small">{{ $trainer?->email ?? '' }}</p>
                </div>
            </div>

            <div class="profile-grid">
                <div class="info-item">
                    <span class="info-label">{{ __('Age') }}</span>
                    <span class="info-value">{{ $trainer?->age ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('Height') }}</span>
                    <span class="info-value">{{ $trainer?->height ? $trainer->height.' '.__('cm') : '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('Weight') }}</span>
                    <span class="info-value">{{ $trainer?->weight ? $trainer->weight.' '.__('kg') : '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">{{ __('Role') }}</span>
                    <span class="info-value">{{ __('Trainer') }}</span>
                </div>
            </div>

            <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn btn-ghost" id="toggleEditBtn">
                    <i class="fas fa-user-edit"></i> {{ __('Edit Profile') }}
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
                        <label>{{ __('Name') }}</label>
                        <input class="form-control" type="text" name="name" value="{{ $trainer?->name ?? '' }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Age') }}</label>
                        <input class="form-control" type="number" name="age" value="{{ $trainer?->age ?? '' }}">
                    </div>
                </div>

                <div class="grid two-col">
                    <div class="form-group">
                        <label>{{ __('Height (cm)') }}</label>
                        <input class="form-control" type="number" name="height" value="{{ $trainer?->height ?? '' }}">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Weight (kg)') }}</label>
                        <input class="form-control" type="number" name="weight" value="{{ $trainer?->weight ?? '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ __('Profile Photo') }}</label>
                    <input class="form-control" type="file" name="photo" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save"></i> {{ __('Save Changes') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Plans --}}
    @if($trainee)

        @if(!$selectedType)
            <div class="card" style="margin-top:14px;">
                <b>{{ __('⚠️ Please open the plan form from the Requests page.') }}</b>
            </div>
        @endif

        <div class="plans-stack" style="margin-top:14px;">

            {{-- Workout Weekly --}}
            @if($selectedType === 'workout')
                <div class="card plan-card">
                    <div class="card-head">
                        <div class="card-title">
                            <i class="fas fa-running"></i>
                            <h3>{{ __('Add Workout Plan') }}</h3>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('trainer.addWorkout') }}" class="form">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $trainee->id }}">
                        <input type="hidden" name="request_id" value="{{ $requestId }}">

                        <div class="form-group">
                            <label>{{ __('Plan Title') }}</label>
                            <input type="text" name="title" required class="form-control" placeholder="{{ __('e.g. Strength Plan - Week 1') }}">
                        </div>

                        @if(isset($workoutTemplates) && $workoutTemplates->count())
                            <div class="form-group">
                                <label>{{ __('Load Workout Template') }}</label>
                                <select class="form-control" onchange="loadWorkoutTemplateWeekly(this)">
                                    <option value="">{{ __('-- Select Template --') }}</option>
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
                                            <h4>{{ __($day) }}</h4>
                                        </div>

                                        <button type="button" class="add-mini-btn" data-type="workout" data-day="{{ $day }}">
                                            <i class="fas fa-plus"></i> {{ __('Add Exercise') }}
                                        </button>
                                    </div>

                                    <div class="rows-wrap rows-day" id="workout-rows-{{ $day }}">
                                        <div class="workout-row">
                                            <input type="hidden" name="day[]" value="{{ $day }}">

                                            <select name="muscle[]" required>
                                                <option value="">{{ __('Muscle') }}</option>
                                                @foreach(['Chest','Back','Shoulders','Legs','Abs','Biceps','Triceps'] as $muscle)
                                                    <option value="{{ $muscle }}">{{ \App\Models\Exercise::translateMuscleName($muscle) }}</option>
                                                @endforeach
                                            </select>

                                            <input type="text" name="exercise[]" placeholder="{{ __('Exercise (Full name)') }}" required>
                                            <input type="number" name="sets[]" placeholder="{{ __('Sets') }}" required>
                                            <input type="text" name="reps[]" placeholder="{{ __('Reps (e.g. 10-12)') }}" required>

                                            <button type="button" class="remove-row" onclick="removeWorkoutRow(this)" title="{{ __('Remove Row') }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> {{ __('Submit Workout Plan') }}
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
                            <h3>{{ __('Add Nutrition Plan') }}</h3>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('trainer.addNutrition') }}" class="form">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $trainee->id }}">
                        <input type="hidden" name="request_id" value="{{ $requestId }}">

                        <div class="form-group">
                            <label>{{ __('Plan Title') }}</label>
                            <input type="text" name="title" required class="form-control" placeholder="{{ __('e.g. Cutting Plan - Week 1') }}">
                        </div>

                        @if(isset($nutritionTemplates) && $nutritionTemplates->count())
                            <div class="form-group">
                                <label>{{ __('Load Nutrition Template') }}</label>
                                <select class="form-control" onchange="loadNutritionTemplateWeekly(this)">
                                    <option value="">{{ __('-- Select Template --') }}</option>
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
                                            <h4>{{ __($day) }}</h4>
                                        </div>

                                        <button type="button" class="add-mini-btn" data-type="nutrition" data-day="{{ $day }}">
                                            <i class="fas fa-plus"></i> {{ __('Add Meal') }}
                                        </button>
                                    </div>

                                    <div class="rows-wrap rows-day" id="nutrition-rows-{{ $day }}">
                                        <div class="nutrition-row">
                                            <input type="hidden" name="day[]" value="{{ $day }}">

                                            <input type="number" name="meal_number[]" placeholder="{{ __('Meal #') }}" min="1" required>
                                            <input type="text" name="description[]" placeholder="{{ __('Meal Description (Full text)') }}" required>
                                            <input type="number" name="calories[]" placeholder="{{ __('Calories') }}" required>
                                            <input type="number" name="protein[]" placeholder="{{ __('Protein (g)') }}" step="0.1" required>
                                            <input type="number" name="carbs[]" placeholder="{{ __('Carbs (g)') }}" step="0.1" required>
                                            <input type="number" name="fat[]" placeholder="{{ __('Fat (g)') }}" step="0.1" required>

                                            <button type="button" class="remove-row" onclick="removeNutritionRow(this)" title="{{ __('Remove Row') }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> {{ __('Submit Nutrition Plan') }}
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