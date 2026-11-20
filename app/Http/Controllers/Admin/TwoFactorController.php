<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class TwoFactorController extends Controller
{
    public function challenge(Request $request)
    {
        if (! $request->user()->hasTwoFactor() || $request->session()->get('2fa_passed')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.security.challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        if (! Totp::verify($request->user()->two_factor_secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code incorrect ou expiré.']);
        }

        $request->session()->put('2fa_passed', true);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function setup(Request $request)
    {
        $user = $request->user();
        if ($user->hasTwoFactor()) {
            return view('admin.security.setup', ['enabled' => true]);
        }

        $secret = $request->session()->get('2fa_setup_secret') ?? Totp::generateSecret();
        $request->session()->put('2fa_setup_secret', $secret);

        return view('admin.security.setup', [
            'enabled' => false,
            'secret' => $secret,
            'uri' => Totp::uri($secret, $user->email),
        ]);
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);
        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret || ! Totp::verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Code incorrect : vérifiez l’heure de votre téléphone et réessayez.']);
        }

        $request->user()->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();
        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('2fa_passed', true);
        AuditLogger::log('2fa_activee', 'Double authentification activée');

        return redirect()->route('admin.dashboard')->with('status', 'Double authentification activée.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();

        if ($user->mustUseTwoFactor()) {
            return back()->withErrors(['password' => 'Votre rôle exige la double authentification : elle ne peut pas être désactivée.']);
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        AuditLogger::log('2fa_desactivee', 'Double authentification désactivée');

        return redirect()->route('admin.2fa.setup')->with('status', 'Double authentification désactivée.');
    }

    public function passwordForm()
    {
        return view('admin.security.password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        $request->user()->update(['password' => $data['password']]);
        AuditLogger::log('mot_de_passe', 'Mot de passe modifié');

        return redirect()->route('admin.dashboard')->with('status', 'Mot de passe modifié.');
    }
}
