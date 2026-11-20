<?php

namespace App\Providers;

use App\Models\OfficialDocument;
use App\Models\User;
use App\Policies\OfficialDocumentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('vendor.pagination.evc');

        // Permissions simples (config/evc.php → permissions)
        foreach (array_keys(config('evc.permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
        Gate::policy(OfficialDocument::class, OfficialDocumentPolicy::class);

        // Vérification publique : empêche les recherches automatisées massives
        RateLimiter::for('verification', fn (Request $request) => [
            Limit::perMinute(20)->by($request->ip()),
            Limit::perHour(200)->by($request->ip()),
        ]);

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(15)->by($request->ip()),
        ]);

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by(($request->user()?->id ?? 0).'|'.$request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
