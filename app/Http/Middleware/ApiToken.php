<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Accès réservé à My EVC : en-tête « Authorization: Bearer <EVC_API_TOKEN> ». */
class ApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('evc.api_token');

        if ($token === '' || strlen($token) < 32) {
            return response()->json(['message' => 'API désactivée.'], 503);
        }
        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Jeton invalide.'], 401);
        }

        return $next($request);
    }
}
