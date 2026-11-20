<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = strtolower($credentials['email']);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Adresse e-mail ou mot de passe incorrect, ou compte désactivé.']);
        }

        $request->session()->regenerate();
        $request->session()->put('2fa_passed', false);
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();
        AuditLogger::log('connexion', 'Connexion de '.$user->email);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLogger::log('deconnexion', 'Déconnexion');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
