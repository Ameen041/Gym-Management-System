<?php

namespace App\Http\Controllers;

use App\Models\CoachPackage;
use App\Models\User;
use App\Support\Money;
use App\Support\Notify;
use Illuminate\Http\Request;

/**
 * Every coach's packages in one place: the admin can change a price, hide a package,
 * and lock a price so the coach cannot change it.
 */
class AdminPackageController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $coach = $request->integer('coach') ?: null;
        $outOfRange = $request->boolean('out');

        $packages = CoachPackage::query()
            ->with('coach:id,name')
            ->whereHas('coach', fn ($q) => $q->where('role', 'trainer'))
            ->when($coach, fn ($q) => $q->where('coach_id', $coach))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('coach', fn ($c) => $c->where('name', 'like', "%{$search}%"))))
            ->when($outOfRange, fn ($q) => $q->outOfRange())
            ->orderBy('coach_id')->orderBy('price')
            ->paginate(30)->withQueryString();

        return view('admin.packages.index', [
            'packages'   => $packages,
            'search'     => $search,
            'coach'      => $coach ? User::find($coach) : null,
            'outOfRange' => $outOfRange,
            'outCount'   => CoachPackage::outOfRange()->count(),
        ]);
    }

    public function update(Request $request, CoachPackage $package)
    {
        $data = $request->validate([
            'price'        => ['required', 'numeric', 'min:1', 'max:10000'],
            'is_active'    => ['nullable', 'boolean'],
            'price_locked' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['price_locked'] = $request->boolean('price_locked');

        $priceChanged = (float) $package->price !== (float) $data['price'];
        $lockChanged = $package->price_locked !== $data['price_locked'];
        $hidden = $package->is_active && !$data['is_active'];
        $package->update($data);

        // the coach learns about it in their notifications (new subscriptions use the new price)
        $params = ['title' => $package->title, 'price' => Money::format($package->price)];
        $link = Notify::link('coach.packages.index');
        if ($priceChanged || ($lockChanged && $package->price_locked)) {
            Notify::user($package->coach, $package->price_locked
                ? 'The admin set the price of your package ":title" to :price. Only the admin can change it now.'
                : 'The admin changed the price of your package ":title" to :price.', $params, $link, 'fa-tags');
        } elseif ($lockChanged) {
            Notify::user($package->coach, 'You can set the price of your package ":title" yourself again.', $params, $link, 'fa-tags');
        }
        if ($hidden) {
            Notify::user($package->coach, 'The admin hid your package ":title" from trainees.', $params, $link, 'fa-eye-slash');
        }

        return back()->with('status', __('Package ":title" saved.', ['title' => $package->title]));
    }
}
