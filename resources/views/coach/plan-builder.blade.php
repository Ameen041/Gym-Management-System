<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type === 'workout' ? __('Workout plan') : __('Nutrition plan') }} · {{ $subscription->trainee->name }} | {{ __('Ameen Gym') }}</title>
    @include('partials.head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/trainer-dashboard.css') }}">
</head>
<body>
@php
    use App\Models\Exercise;
    $muscleOptions = collect(Exercise::MUSCLE_GROUPS)->pluck('label')->all();
    $isWorkout = $type === 'workout';

    // After a validation error, show what the coach typed instead of losing it
    if (is_array(old('day'))) {
        $fields = $isWorkout
            ? ['muscle', 'exercise', 'exercise_id', 'sets', 'reps']
            : ['meal_number', 'description', 'calories', 'protein', 'carbs', 'fat'];
        $details = [];
        foreach (old('day') as $i => $oldDay) {
            $row = [];
            foreach ($fields as $field) {
                $row[$field] = old($field)[$i] ?? '';
            }
            $details[$oldDay][] = $row;
        }
    }
@endphp
<div class="page">
    <div class="topbar">
        <div class="brand">
            <div class="brand-icon"><i class="fas {{ $isWorkout ? 'fa-dumbbell' : 'fa-apple-alt' }}"></i></div>
            <div class="brand-text">
                <h1>{{ $plan ? __('Update plan') : __('Create plan') }}: {{ $isWorkout ? __('Workout plan') : __('Nutrition plan') }}</h1>
                <p>{{ __('For :name', ['name' => $subscription->trainee->name]) }} · {{ $subscription->package_title }}</p>
            </div>
        </div>
        <div class="topbar-right">
            @include('partials.lang-switch')
            <a href="{{ route('coach.clients.show', $subscription) }}" class="btn btn-ghost">
                <i class="fas fa-arrow-left"></i> {{ __('Back to client') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="margin:12px 0;">
            <i class="fas fa-exclamation-triangle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="card plan-card" style="margin-top:14px;">
        <form method="POST" action="{{ route('coach.plans.save', [$subscription, $type]) }}" class="form">
            @csrf

            <div class="form-group">
                <label>{{ __('Plan Title') }}</label>
                <input type="text" name="title" required class="form-control"
                       value="{{ old('title', $plan->title ?? '') }}"
                       placeholder="{{ $isWorkout ? __('e.g. Strength Plan - Week 1') : __('e.g. Cutting Plan - Week 1') }}">
            </div>

            @if($templates->count())
                <div class="form-group">
                    <label>{{ $isWorkout ? __('Load Workout Template') : __('Load Nutrition Template') }}</label>
                    <select class="form-control" onchange="{{ $isWorkout ? 'loadWorkoutTemplateWeekly(this)' : 'loadNutritionTemplateWeekly(this)' }}">
                        <option value="">{{ __('-- Select Template --') }}</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}" data-body="{{ trim($tpl->plan_details) }}">{{ $tpl->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($isWorkout)
                <div class="hint-pill" style="margin-bottom:12px">
                    <i class="fas fa-magic"></i>
                    {{ __('Start typing an exercise name and pick it from the library: the muscle is filled in and your client gets the exercise video.') }}
                    <a href="{{ route('exercises.index') }}" target="_blank" rel="noopener">{{ __('Browse the library') }}</a>
                </div>

                <datalist id="exercise-library">
                    @foreach($library as $exercise)
                        <option value="{{ $exercise->display_name }}"
                                data-id="{{ $exercise->id }}"
                                data-en="{{ $exercise->name }}"
                                data-muscle="{{ Exercise::MUSCLE_GROUPS[$exercise->muscle_group]['label'] ?? '' }}"></option>
                    @endforeach
                </datalist>
            @endif

            <div class="weekly-wrap">
                @foreach($weekDays as $day)
                    @php($rows = $details[$day] ?? [[]])
                    <div class="day-card">
                        <div class="day-head">
                            <div class="day-title">
                                <i class="fas fa-calendar-day"></i>
                                <h4>{{ __($day) }}</h4>
                            </div>
                            <button type="button" class="add-mini-btn" data-type="{{ $type }}" data-day="{{ $day }}">
                                <i class="fas fa-plus"></i> {{ $isWorkout ? __('Add Exercise') : __('Add Meal') }}
                            </button>
                        </div>

                        <div class="rows-wrap rows-day" id="{{ $type }}-rows-{{ $day }}">
                            @foreach($rows as $row)
                                @if($isWorkout)
                                    <div class="workout-row">
                                        <input type="hidden" name="day[]" value="{{ $day }}">
                                        <input type="hidden" name="exercise_id[]" value="{{ $row['exercise_id'] ?? '' }}">
                                        <select name="muscle[]">
                                            <option value="">{{ __('Muscle') }}</option>
                                            @foreach($muscleOptions as $muscle)
                                                <option value="{{ $muscle }}" @selected(strcasecmp($row['muscle'] ?? '', $muscle) === 0)>{{ Exercise::translateMuscleName($muscle) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="exercise[]" list="exercise-library" autocomplete="off"
                                               value="{{ $row['exercise'] ?? '' }}" placeholder="{{ __('Exercise (Full name)') }}">
                                        <input type="text" name="sets[]" inputmode="numeric" value="{{ $row['sets'] ?? '' }}" placeholder="{{ __('Sets') }}">
                                        <input type="text" name="reps[]" value="{{ $row['reps'] ?? '' }}" placeholder="{{ __('Reps (e.g. 10-12)') }}">
                                        <button type="button" class="remove-row" title="{{ __('Remove Row') }}"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                @else
                                    <div class="nutrition-row">
                                        <input type="hidden" name="day[]" value="{{ $day }}">
                                        <input type="number" name="meal_number[]" value="{{ $row['meal_number'] ?? '' }}" placeholder="{{ __('Meal #') }}" min="1">
                                        <input type="text" name="description[]" value="{{ $row['description'] ?? '' }}" placeholder="{{ __('Meal Description (Full text)') }}">
                                        <input type="number" name="calories[]" value="{{ $row['calories'] ?? '' }}" placeholder="{{ __('Calories') }}">
                                        <input type="number" name="protein[]" value="{{ $row['protein'] ?? '' }}" placeholder="{{ __('Protein (g)') }}" step="0.1">
                                        <input type="number" name="carbs[]" value="{{ $row['carbs'] ?? '' }}" placeholder="{{ __('Carbs (g)') }}" step="0.1">
                                        <input type="number" name="fat[]" value="{{ $row['fat'] ?? '' }}" placeholder="{{ __('Fat (g)') }}" step="0.1">
                                        <button type="button" class="remove-row" title="{{ __('Remove Row') }}"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="muted small" style="margin:10px 0">{{ __('Days left empty are rest days.') }}</p>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-paper-plane"></i> {{ $plan ? __('Save and send the update') : __('Send the plan to my client') }}
            </button>
        </form>
    </div>
</div>
<script src="{{ asset('js/trainer-dashboard.js') }}"></script>
</body>
</html>
