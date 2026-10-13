<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Workout Plan | Ameen Gym') }}</title>
    @include('partials.head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/workout-plan.css') }}">
</head>
<body>
@php
    $details = json_decode($plan->plan_details, true);
    // Library exercises used in this plan, to link each row to its video
    $libraryIds = collect($details ?: [])->flatten(1)->pluck('exercise_id')->filter()->unique();
    $library = \App\Models\Exercise::whereIn('id', $libraryIds)->get()->keyBy('id');
@endphp
<div class="dashboard-container">
    <div class="plan-card">
        @include('plans._header', ['icon' => 'fa-dumbbell', 'heading' => __('Workout Plan')])

            @if(is_array($details))
                <div class="table-container">
                    <table class="workout-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-day"></i> {{ __('Day') }}</th>
                                <th><i class="fas fa-dumbbell"></i> {{ __('Muscle') }}</th>
                                <th><i class="fas fa-running"></i> {{ __('Exercise') }}</th>
                                <th><i class="fas fa-layer-group"></i> {{ __('Sets') }}</th>
                                <th><i class="fas fa-redo"></i> {{ __('Reps') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($details as $day => $exercises)
                            <tr class="day-row">
                                <td colspan="5">
                                    <i class="fas fa-calendar-day"></i>
                                    <b>{{ __($day) }}</b>
                                    <span class="day-count">({{ __(':count exercises', ['count' => count($exercises)]) }})</span>
                                </td>
                            </tr>
                            @foreach($exercises as $exercise)
                                @php($libraryExercise = $library[$exercise['exercise_id'] ?? 0] ?? null)
                                <tr>
                                    <td class="day-empty"></td>
                                    <td>{{ \App\Models\Exercise::translateMuscleName($exercise['muscle'] ?? '') }}</td>
                                    <td>
                                        @if($libraryExercise)
                                            <a href="{{ route('exercises.show', $libraryExercise) }}" class="exercise-link">
                                                <i class="fas fa-play-circle"></i> {{ $libraryExercise->display_name }}
                                            </a>
                                        @else
                                            {{ $exercise['exercise'] ?? '' }}
                                        @endif
                                    </td>
                                    <td>{{ $exercise['sets'] ?? '' }}</td>
                                    <td>{{ $exercise['reps'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($library->isNotEmpty())
                    <p class="muted" style="margin-top:10px"><i class="fas fa-play-circle"></i> {{ __('Tap an exercise to watch how to do it.') }}</p>
                @endif
            @else
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>{{ __('No valid workout plan data available.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
<style>
    .exercise-link{color:var(--primary);font-weight:700;text-decoration:none}
    .exercise-link:hover{text-decoration:underline}
    .muted{color:rgba(0,0,0,.55)}
</style>
<script src="{{ asset('js/workout-plan.js') }}"></script>
</body>
</html>
