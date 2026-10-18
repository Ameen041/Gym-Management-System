<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\CoachPackage;
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
        $package->update($this->validated($request));

        return redirect()->route('coach.packages.index')->with('success', __('Package saved.'));
    }

    public function destroy(CoachPackage $package)
    {
        $this->authorizeOwner($package);
        $package->delete(); // past subscriptions keep their own copy of the package details

        return redirect()->route('coach.packages.index')->with('success', __('Package deleted.'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'              => ['required', 'string', 'max:120', new NoContactDetails],
            'description'        => ['nullable', 'string', 'max:1000', new NoContactDetails],
            'includes_workout'   => ['nullable', 'boolean'],
            'includes_nutrition' => ['nullable', 'boolean'],
            'price'              => ['required', 'numeric', 'min:5', 'max:1000'],
            'duration_days'      => ['required', Rule::in(self::DURATIONS)],
        ]);

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
