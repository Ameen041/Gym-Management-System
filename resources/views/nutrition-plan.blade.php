<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Nutrition Plan | Ameen Gym') }}</title>
    @include('partials.head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/workout-plan.css') }}">
</head>
<body>
@php($details = json_decode($plan->plan_details, true))
<div class="dashboard-container">
    <div class="plan-card">
        @include('plans._header', ['icon' => 'fa-apple-alt', 'heading' => __('Nutrition Plan')])

            @if(is_array($details))
                <div class="table-container">
                    <table class="workout-table">
                        <thead>
                            <tr>
                                <th>{{ __('Day') }}</th>
                                <th>{{ __('Meal #') }}</th>
                                <th>{{ __('Meal') }}</th>
                                <th>{{ __('Calories') }}</th>
                                <th>{{ __('Protein (g)') }}</th>
                                <th>{{ __('Carbs (g)') }}</th>
                                <th>{{ __('Fat (g)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($details as $day => $meals)
                            <tr class="day-row">
                                <td colspan="7">
                                    <i class="fas fa-calendar-day"></i>
                                    <b>{{ __($day) }}</b>
                                    <span class="day-count">({{ __(':count meals', ['count' => count($meals)]) }})</span>
                                </td>
                            </tr>
                            @foreach($meals as $meal)
                                <tr>
                                    <td class="day-empty"></td>
                                    <td>{{ $meal['meal_number'] ?? '' }}</td>
                                    <td>{{ $meal['description'] ?? '' }}</td>
                                    <td>{{ $meal['calories'] ?? '' }}</td>
                                    <td>{{ $meal['protein'] ?? '' }}</td>
                                    <td>{{ $meal['carbs'] ?? '' }}</td>
                                    <td>{{ $meal['fat'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="no-data">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>{{ __('No valid nutrition plan data available.') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
<style>.muted{color:rgba(0,0,0,.55)}</style>
<script src="{{ asset('js/workout-plan.js') }}"></script>
</body>
</html>
