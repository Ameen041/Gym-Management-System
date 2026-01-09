<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

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
        return redirect()->route('admin.users.index')->with('status','User created successfully.');
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
        return redirect()->route('admin.users.index')->with('status','User updated.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if (auth()->id() === $user->id) return back()->with('status',"You can't delete your own account.");
        $user->delete();
        return redirect()->route('admin.users.index')->with('status','User deleted.');
    }


    public function toggleActive($id)
{
    $user = User::findOrFail($id);

    if (auth()->id() === $user->id) {
        return back()->with('status', "You can't suspend your own account.");
    }

    if ($user->role === 'admin') {
        return back()->with('status', "You can't suspend an admin.");
    }

    $user->is_active = !$user->is_active;
    $user->save();

    return back()->with('status', 'User status updated: '.($user->is_active ? 'Activated' : 'Suspended'));
}

public function payments($id)
{
    $user = User::findOrFail($id);

    
    if ($user->role !== 'trainee') {
        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Payments can only be managed for trainees.');
    }

    $payments = Payment::where('user_id', $user->id)
        ->orderByDesc('paid_at')
        ->orderByDesc('id')
        ->get();

    return view('admin.users.payments', compact('user', 'payments'));
}

public function storePayment(Request $request, $id)
{
    $user = User::findOrFail($id);

    $data = $request->validate([
        'amount'        => ['required','numeric','min:0'],
        'method'        => ['nullable','string','max:50'],
        'paid_at'       => ['required','date'],
        'period_start'  => ['required','date'],
        'period_end'    => ['required','date','after_or_equal:period_start'],
        'reference'     => ['nullable','string','max:100'],
        'notes'         => ['nullable','string'],
    ]);

    $data['user_id'] = $user->id;

    Payment::create($data);

    return redirect()
        ->route('admin.users.payments', $user->id)
        ->with('status', 'Payment recorded successfully.');
}

}