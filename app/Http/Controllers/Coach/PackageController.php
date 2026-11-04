<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\CoachPackage;
use App\Models\Setting;
use App\Support\Money;
use App\Rules\NoContactDetails;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PackageController extends Controller
{
    public const DURATIONS = [30, 60, 90];

    public function index()
    {
        $packages = auth()->user()->packages()->orderByDesc('is_active')->orderBy('price')->get();

        return view('coach.packages', compact('packages'));
    }

    public function store(Request $request)
    {
        auth()->user()->packages()->create($this->validated($request));
        auth()->user()->ensureCoachProfile()->notifyAdminsIfReady();

        return redirect()->route('coach.packages.index')->with('success', __('Package saved.'));
    }

    public function update(Request $request, CoachPackage $package)
    {
        $this->authorizeOwner($package);
        $package->update($this->validated($request, $package));

        return redirect()->route('coach.packages.index')->with('success', __('Package saved.'));
    }

    public function destroy(CoachPackage $package)
    {
        $this->authorizeOwner($package);
        $package->delete(); // past subscriptions keep their own copy of the package details

        return redirect()->route('coach.packages.index')->with('success', __('Package deleted.'));
    }

    /** A package whose price the admin locked keeps its price and duration, whatever the form sends. */
    private function validated(Request $request, ?CoachPackage $package = null): array
    {
        $locked = $package?->price_locked ?? false;
        $data = $request->validate([
            'title'              => ['required', 'string', 'max:120', new NoContactDetails],
            'description'        => ['nullable', 'string', 'max:1000', new NoContactDetails],
            'includes_workout'   => ['nullable', 'boolean'],
            'includes_nutrition' => ['nullable', 'boolean'],
        ] + ($locked ? [] : [
            'duration_days'      => ['required', Rule::in(self::DURATIONS)],
            'price'              => ['required', 'numeric', function ($attribute, $value, $fail) use ($request) {
                // the range the admin set, for the chosen duration
                [$min, $max] = Setting::packagePriceRange((int) $request->input('duration_days'));
                if ((float) $value < $min || (float) $value > $max) {
                    $fail(__('The price of a :days-day package must be between :min and :max.', [
                        'days' => (int) $request->input('duration_days'), 'min' => Money::format($min), 'max' => Money::format($max),
                    ]));
                }
            }],
        ]));

        $data['includes_workout']   = $request->boolean('includes_workout');
        $data['includes_nutrition'] = $request->boolean('includes_nutrition');
        $data['is_active']          = $request->boolean('is_active');

        if (!$data['includes_workout'] && !$data['includes_nutrition']) {
            throw ValidationException::withMessages([
                'includes_workout' => __('A package must include a workout plan, a nutrition plan or both.'),
            ]);
        }

        return $data;
    }

    private function authorizeOwner(CoachPackage $package): void
    {
        abort_unless($package->coach_id === auth()->id(), 404);
    }
}
