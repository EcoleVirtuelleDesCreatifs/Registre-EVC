<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('users.manage');

        return view('admin.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        Gate::authorize('users.manage');

        return view('admin.users.form', ['user' => new User(['role' => 'secretariat', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        Gate::authorize('users.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(array_keys(config('evc.roles')))],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);
        $this->guardRole($request, $data['role']);

        $user = User::create([...$data, 'email' => strtolower($data['email']), 'is_active' => true]);
        AuditLogger::log('utilisateur_cree', "Utilisateur créé : {$user->email} ({$user->roleLabel()})");

        return redirect()->route('admin.utilisateurs.index')->with('status', 'Utilisateur créé.');
    }

    public function edit(User $user)
    {
        Gate::authorize('users.manage');

        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('users.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(array_keys(config('evc.roles')))],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()],
            'reset_2fa' => ['nullable', 'boolean'],
        ]);
        $this->guardRole($request, $data['role']);
        $active = $request->boolean('is_active');

        if ($user->is($request->user()) && (! $active || $data['role'] !== $user->role)) {
            return back()->withErrors(['role' => 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre compte.']);
        }
        if ($user->role === 'direction_generale' && ($data['role'] !== 'direction_generale' || ! $active)
            && User::where('role', 'direction_generale')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['role' => 'Il doit rester au moins un compte Direction générale actif.']);
        }

        $before = ['role' => $user->role, 'is_active' => $user->is_active];
        $user->fill(['name' => $data['name'], 'role' => $data['role'], 'is_active' => $active]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        if ($request->boolean('reset_2fa')) {
            $user->two_factor_secret = null;
            $user->two_factor_confirmed_at = null;
        }
        $user->save();

        AuditLogger::log('utilisateur_modifie', "Utilisateur modifié : {$user->email}", null, null, [
            'avant' => $before, 'apres' => ['role' => $user->role, 'is_active' => $user->is_active],
            'mot_de_passe_reinitialise' => ! empty($data['password']), '2fa_reinitialisee' => $request->boolean('reset_2fa'),
        ]);

        return redirect()->route('admin.utilisateurs.index')->with('status', 'Utilisateur mis à jour.');
    }

    /** L'administrateur technique ne peut pas créer de comptes de direction. */
    private function guardRole(Request $request, string $role): void
    {
        if ($request->user()->role !== 'direction_generale' && in_array($role, ['direction_generale', 'direction_pedagogique'], true)) {
            abort(403, 'Seule la Direction générale peut attribuer un rôle de direction.');
        }
    }
}
