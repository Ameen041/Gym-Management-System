<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Nutrition Plan | Ameen Gym') }}</title>
    @include('partials.head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/workout-plan.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nutrition-plan.css') }}">
</head>
<body>
@php
    use App\Support\NutritionMath;
    $details = json_decode($plan->plan_details, true);
    $dayTotals = is_array($details) ? array_map(fn ($meals) => NutritionMath::sum((array) $meals), $details) : [];
    $average = $dayTotals ? array_map(fn ($v) => $v / count($dayTotals), NutritionMath::sum($dayTotals)) : null;
    $macroLabels = ['protein' => __('Protein'), 'carbs' => __('Carbs'), 'fat' => __('Fat')];
@endphp
<div class="dashboard-container">
    <div class="plan-card">
        @include('plans._header', ['icon' => 'fa-apple-alt', 'heading' => __('Nutrition Plan')])

            @if(is_array($details) && $details)
                <div class="np-average">
                    <span class="np-average-label"><i class="fas fa-chart-pie"></i> {{ __('Daily average') }}</span>
                    <span class="np-chip np-chip-kcal">{{ round($average['calories']) }} {{ __('kcal') }}</span>
                    @foreach($macroLabels as $macro => $label)
                        <span class="np-chip">{{ $label }} {{ NutritionMath::number($average[$macro]) }} {{ __('g') }}</span>
                    @endforeach
                </div>

                <div class="np-days">
                @foreach($details as $day => $meals)
                    <section class="np-day">
                        <header class="np-day-head">
                            <h3><i class="fas fa-calendar-day"></i> {{ __($day) }} <small>({{ __(':count meals', ['count' => count($meals)]) }})</small></h3>
                            <div class="np-chips">
                                <span class="np-chip np-chip-kcal">{{ round($dayTotals[$day]['calories']) }} {{ __('kcal') }}</span>
                                @foreach($macroLabels as $macro => $label)
                                    <span class="np-chip">{{ $label }} {{ NutritionMath::number($dayTotals[$day][$macro]) }} {{ __('g') }}</span>
                                @endforeach
                            </div>
                        </header>

                        @foreach($meals as $meal)
                            <div class="np-meal">
                                <div class="np-meal-head">
                                    <strong>{{ __('Meal') }} {{ $meal['meal_number'] ?? $loop->iteration }}</strong>
                                    @if(isset($meal['items']) && filled($meal['description'] ?? null))
                                        <span class="np-note">{{ $meal['description'] }}</span>
                                    @endif
                                    <span class="np-meal-kcal">{{ round((float) ($meal['calories'] ?? 0)) }} {{ __('kcal') }}</span>
                                </div>

                                @if(isset($meal['items']))
                                    <ul class="np-items">
                                        @foreach($meal['items'] as $item)
                                            <li>
                                                <span class="np-food">{{ NutritionMath::itemName($item) }}</span>
                                                <span class="np-amount">{{ NutritionMath::itemAmount($item) }}</span>
                                                <span class="np-item-kcal">{{ round((float) ($item['calories'] ?? 0)) }} {{ __('kcal') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="np-legacy">{{ $meal['description'] ?? '' }}</p>
                                @endif

                                <div class="np-meal-macros">
                                    @foreach($macroLabels as $macro => $label)
                                        <span>{{ $label }}: <b>{{ NutritionMath::number($meal[$macro] ?? 0) }} {{ __('g') }}</b></span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endforeach
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
