<?php

namespace App\Support;

use App\Models\Food;
use Illuminate\Validation\ValidationException;

/**
 * Nutrition plan data: meals made of foods, with calories / macros
 * calculated per food, per meal and per day.
 *
 * Stored plan_details (JSON):
 *   { "Saturday": [ { "meal_number": 1, "description": "Breakfast",
 *                     "items": [ { "food_id": 3, "name": "...", "name_ar": "...", "qty": 2, "unit": "serving",
 *                                  "grams": 100, "serving_name": "...", "serving_name_ar": "...",
 *                                  "calories": 143, "protein": 12.6, "carbs": 0.7, "fat": 9.5 } ],
 *                     "calories": 143, "protein": 12.6, "carbs": 0.7, "fat": 9.5 } ] }
 *
 * Plans created before the calculator have meals without "items"
 * (just description + calories/protein/carbs/fat); they are still shown as they are.
 */
class NutritionMath
{
    public const MACROS = ['calories', 'protein', 'carbs', 'fat'];

    // A day is on target when each value is this close to the daily target (0.05 = ±5%)
    public const TARGET_RANGE = ['calories' => 0.05, 'protein' => 0.10, 'carbs' => 0.10, 'fat' => 0.10];

    // What a coach can set as daily targets: [min, max]
    public const TARGET_LIMITS = ['calories' => [800, 6000], 'protein' => [20, 500], 'carbs' => [0, 900], 'fat' => [10, 400]];
    private const MAX_MEALS_PER_DAY = 12;
    private const MAX_ITEMS_PER_MEAL = 30;

    /**
     * Build the plan details from the builder form (meals[key][day|number|note|items][...]).
     * Library foods are always recalculated here from the database values.
     */
    public static function fromRequest(array $meals, array $weekDays): array
    {
        $foodIds = collect($meals)->pluck('items')->filter(fn ($items) => is_array($items))
            ->flatten(1)->pluck('food_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $foods = Food::whereIn('id', $foodIds)->get()->keyBy('id');

        $details = [];
        foreach ($meals as $meal) {
            if (!is_array($meal) || !in_array($meal['day'] ?? null, $weekDays, true)) {
                continue;
            }
            $day = $meal['day'];
            $position = count($details[$day] ?? []) + 1;
            $number = (int) ($meal['number'] ?? 0) ?: $position;
            $note = mb_substr(trim((string) ($meal['note'] ?? '')), 0, 255);
            $where = ['day' => __($day), 'meal' => $number];

            $items = [];
            foreach (array_slice((array) ($meal['items'] ?? []), 0, self::MAX_ITEMS_PER_MEAL) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $name = mb_substr(trim((string) ($item['name'] ?? '')), 0, 255);
                if ($name === '') {
                    continue; // empty row
                }
                $food = $foods[(int) ($item['food_id'] ?? 0)] ?? null;
                $qty = is_numeric($item['qty'] ?? null) ? (float) $item['qty'] : 0;

                if ($food) {
                    $unit = ($item['unit'] ?? 'g') === 'serving' && $food->serving_grams ? 'serving' : 'g';
                    $grams = $unit === 'serving' ? $qty * $food->serving_grams : $qty;
                    if ($qty <= 0 || $grams > 5000) {
                        throw ValidationException::withMessages(['meals' => __('Enter a valid amount for ":food" (:day, meal :meal).', $where + ['food' => $name])]);
                    }
                    $items[] = [
                        'food_id'         => $food->id,
                        'name'            => $food->name,
                        'name_ar'         => $food->name_ar,
                        'qty'             => round($qty, 2),
                        'unit'            => $unit,
                        'grams'           => round($grams, 1),
                        'serving_name'    => $unit === 'serving' ? $food->serving_name : null,
                        'serving_name_ar' => $unit === 'serving' ? $food->serving_name_ar : null,
                    ] + $food->nutritionFor($grams);
                    continue;
                }

                // A food that is not in the library: the coach enters its values for the amount given
                if (!is_numeric($item['calories'] ?? null)) {
                    throw ValidationException::withMessages(['meals' => __('Enter the calories of ":food" (:day, meal :meal), or pick a food from the list.', $where + ['food' => $name])]);
                }
                $custom = ['food_id' => null, 'name' => $name, 'qty' => $qty > 0 ? round($qty, 2) : null, 'unit' => 'g', 'grams' => $qty > 0 ? round($qty, 1) : null];
                foreach (self::MACROS as $macro) {
                    $value = is_numeric($item[$macro] ?? null) ? (float) $item[$macro] : 0;
                    $custom[$macro] = round(min(max($value, 0), $macro === 'calories' ? 10000 : 1000), $macro === 'calories' ? 0 : 1);
                }
                $items[] = $custom;
            }

            if (!$items && $note === '') {
                continue; // empty meal
            }
            if (count($details[$day] ?? []) >= self::MAX_MEALS_PER_DAY) {
                throw ValidationException::withMessages(['meals' => __('A day can have at most :count meals.', ['count' => self::MAX_MEALS_PER_DAY])]);
            }

            $details[$day][] = ['meal_number' => $number, 'description' => $note, 'items' => $items] + self::sum($items);
        }

        if (!$details) {
            throw ValidationException::withMessages(['meals' => __('The plan is empty. Add at least one meal.')]);
        }

        // Keep the week order
        return array_filter(array_merge(array_fill_keys($weekDays, null), $details));
    }

    /** Totals of a list of items / meals (anything with calories, protein, carbs, fat). */
    public static function sum(iterable $rows): array
    {
        $total = array_fill_keys(self::MACROS, 0.0);
        foreach ($rows as $row) {
            foreach (self::MACROS as $macro) {
                $total[$macro] += is_numeric($row[$macro] ?? null) ? (float) $row[$macro] : 0;
            }
        }

        return [
            'calories' => round($total['calories']),
            'protein'  => round($total['protein'], 1),
            'carbs'    => round($total['carbs'], 1),
            'fat'      => round($total['fat'], 1),
        ];
    }

    /** Name of a stored item in the reader's language. */
    public static function itemName(array $item): string
    {
        return app()->isLocale('ar') && filled($item['name_ar'] ?? null) ? $item['name_ar'] : (string) ($item['name'] ?? '');
    }

    /** "2 × 1 large egg (100 g)" or "150 g". */
    public static function itemAmount(array $item): string
    {
        $grams = $item['grams'] ?? null;
        if (($item['unit'] ?? 'g') === 'serving') {
            $serving = app()->isLocale('ar') && filled($item['serving_name_ar'] ?? null) ? $item['serving_name_ar'] : ($item['serving_name'] ?? '');
            return self::number($item['qty'] ?? 1) . ' × ' . $serving . ($grams ? ' (' . self::number($grams) . ' ' . __('g') . ')' : '');
        }

        return $grams ? self::number($grams) . ' ' . __('g') : '';
    }

    public static function number($value): string
    {
        $value = (float) $value;

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    /**
     * Meals per day in the shape the builder form uses:
     * day => [ ['number' => 1, 'note' => '', 'items' => [ [food_id, name, qty, unit, calories, protein, carbs, fat] ]] ].
     */
    public static function builderDays(array $details, array $weekDays): array
    {
        $days = [];
        foreach ($weekDays as $day) {
            foreach ((array) ($details[$day] ?? []) as $meal) {
                if (!is_array($meal)) {
                    continue;
                }
                if (isset($meal['items']) && is_array($meal['items'])) {
                    $items = array_map(fn ($item) => [
                        'food_id' => $item['food_id'] ?? null,
                        'name'    => !empty($item['food_id']) ? self::itemName($item) : ($item['name'] ?? ''),
                        'qty'     => $item['qty'] ?? '',
                        'unit'    => $item['unit'] ?? 'g',
                    ] + array_intersect_key($item, array_flip(self::MACROS)), $meal['items']);
                    $note = (string) ($meal['description'] ?? '');
                } else {
                    // Plan made before the calculator: one free-text item with the values typed back then
                    $items = [[
                        'food_id' => null, 'name' => (string) ($meal['description'] ?? ''), 'qty' => '', 'unit' => 'g',
                    ] + array_map(fn ($v) => is_numeric($v) ? $v : 0, array_intersect_key($meal, array_flip(self::MACROS)))];
                    $note = '';
                }
                $days[$day][] = ['number' => $meal['meal_number'] ?? '', 'note' => $note, 'items' => $items];
            }
        }

        return $days;
    }

    /** Same shape as builderDays(), from the form input kept after a validation error. */
    public static function builderDaysFromInput(array $meals, array $weekDays): array
    {
        $days = [];
        foreach ($meals as $meal) {
            if (!is_array($meal) || !in_array($meal['day'] ?? null, $weekDays, true)) {
                continue;
            }
            $days[$meal['day']][] = [
                'number' => $meal['number'] ?? '',
                'note'   => $meal['note'] ?? '',
                'items'  => array_values(array_filter((array) ($meal['items'] ?? []), 'is_array')),
            ];
        }

        return $days;
    }
}
