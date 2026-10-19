<?php

namespace App\Http\Controllers;

use App\Support\AccountDeletion;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** A trainee or a coach deletes their own account. */
class AccountController extends Controller
{
    public function confirmDelete(Request $request)
    {
        $user = $request->user();

        return view('account.delete', [
            'user'     => $user,
            'blockers' => AccountDeletion::blockers($user),
            'running'  => $user->traineeSubscriptions()->notEnded()->exists(),
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'password' => ['required', 'string'],
            'confirm'  => ['accepted'],
        ], ['confirm.accepted' => __('Tick the box to confirm.')], ['password' => __('Password')]);

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('The password is not correct.')]);
        }
        if ($blockers = AccountDeletion::blockers($user)) {
            return back()->withErrors(['account' => $blockers[0]]);
        }

        $name = $user->name;
        $role = $user->role;
        AccountDeletion::run($user);
        Notify::admins(':name deleted their account (:role).', ['name' => $name, 'role' => $role === 'trainer' ? __('Coach') : __('Trainee')],
            Notify::link('admin.users.index'), 'fa-user-xmark');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('goodbye', __('Your account was deleted. Thank you for trying Himma.'));
    }
}
