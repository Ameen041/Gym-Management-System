<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query();
    
        if ($request->filled('role')) {
            $q->where('role', $request->role);
        }
    
        if ($request->filled('name')) {
            $name = trim($request->name);
            $q->where('name', 'like', "%{$name}%");
        }
    
        $users = $q->latest()->paginate(20)->withQueryString();
    
        return view('admin.users.index', [
            'users' => $users,
            'currentRole' => $request->role,
        ]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email'=> ['required','email','max:255','unique:users,email'],
            'password'=> ['required','string','min:6'],
            'role' => ['required', Rule::in(['admin','trainer','trainee'])],
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return redirect()->route('admin.users.index')->with('status',__('User created successfully.'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email'=> ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin','trainer','trainee'])],
            'password'=> ['nullable','string','min:6'],
        ]);
        if (!empty($data['password'])) $data['password'] = Hash::make($data['password']);
        else unset($data['password']);
        $user->update($data);
        return redirect()->route('admin.users.index')->with('status',__('User updated.'));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if (auth()->id() === $user->id) return back()->with('status', __("You can't delete your own account."));
        $user->delete();
        return redirect()->route('admin.users.index')->with('status',__('User deleted.'));
    }


    /** Password reset link the admin can send on WhatsApp when the email doesn't arrive. */
    public function resetLink($id)
    {
        $user = User::findOrFail($id);
        abort_if($user->role === 'admin', 403);

        $minutes = config('auth.passwords.users.expire', 60);
        $url = route('password.reset', ['token' => Password::broker()->createToken($user), 'email' => $user->email]);
        $message = __('Hi :name, open this link to set a new password for Himma (valid for :minutes minutes): :url', [
            'name' => $user->name, 'minutes' => $minutes, 'url' => $url,
        ]);

        return back()->with('reset_link', [
            'name'     => $user->name,
            'url'      => $url,
            'minutes'  => $minutes,
            'whatsapp' => ($user->whatsapp_url ?? 'https://wa.me/') . '?text=' . rawurlencode($message),
        ]);
    }

    public function toggleActive($id)
{
    $user = User::findOrFail($id);

    if (auth()->id() === $user->id) {
        return back()->with('status', __("You can't suspend your own account."));
    }

    if ($user->role === 'admin') {
        return back()->with('status', __("You can't suspend an admin."));
    }

    $user->is_active = !$user->is_active;
    $user->save();

    return back()->with('status', __('User status updated: :status', ['status' => $user->is_active ? __('Activated') : __('Suspended')]));
}
}
