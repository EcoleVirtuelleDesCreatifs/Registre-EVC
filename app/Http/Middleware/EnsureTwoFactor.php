<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentification renforcée :
 * - rôle sensible sans double authentification : configuration imposée ;
 * - le code n'est vérifié qu'une seule fois, lors de la configuration.
 */
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $allowed = $request->routeIs('admin.2fa.*', 'admin.logout');

        if (! $user->hasTwoFactor() && $user->mustUseTwoFactor() && ! $allowed) {
            return redirect()->route('admin.2fa.setup')
                ->with('status', 'Votre rôle exige la double authentification. Configurez-la pour continuer.');
        }

        return $next($request);
    }
}
