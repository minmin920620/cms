<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('created_at', 'desc')->paginate(15);
        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = [
            User::ROLE_ADMIN,
            User::ROLE_POLICE_OFFICER,
            User::ROLE_INVESTIGATOR,
            User::ROLE_LGU,
        ];

        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,police_officer,investigator,lgu',
            'phone' => 'nullable|string|max:20',
            'badge_number' => 'nullable|string|max:50|unique:users',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        Audit::log('user.created', $user, [], $user->only([
            'id', 'name', 'email', 'role', 'phone', 'badge_number', 'is_active',
        ]));

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $roles = [
            User::ROLE_ADMIN,
            User::ROLE_POLICE_OFFICER,
            User::ROLE_INVESTIGATOR,
            User::ROLE_LGU,
        ];

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,police_officer,investigator,lgu',
            'phone' => 'nullable|string|max:20',
            'badge_number' => 'nullable|string|max:50|unique:users,badge_number,' . $user->id,
            'is_active' => 'boolean',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $validated['password'] = Hash::make($request->password);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $oldValues = $user->only(array_keys($validated));
        $user->update($validated);
        $changes = $user->getChanges();

        if ($changes) {
            Audit::log('user.updated', $user, array_intersect_key($oldValues, $changes), $changes);
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot archive your own account.');
        }

        $oldValues = $user->only(['id', 'name', 'email', 'role', 'phone', 'badge_number', 'is_active']);
        $user->update(['is_active' => false]);
        Audit::log('user.archived', $user, $oldValues, $user->only([
            'id', 'name', 'email', 'role', 'phone', 'badge_number', 'is_active',
        ]));

        return redirect()->route('users.index')
            ->with('success', 'User archived successfully.');
    }
}

