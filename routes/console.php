<?php

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

/*
| Créer ou mettre à jour un utilisateur :
|   php artisan evc:user "Amandine Kra" amandine_kra@ecolevirtuelledescreatifs.com direction_pedagogique
*/
Artisan::command('evc:user {name} {email} {role} {--password=}', function (string $name, string $email, string $role) {
    if (! array_key_exists($role, config('evc.roles'))) {
        $this->error('Rôle inconnu. Rôles : '.implode(', ', array_keys(config('evc.roles'))));

        return 1;
    }
    $password = $this->option('password') ?: $this->secret('Mot de passe (12 caractères minimum)');
    if (! $password || strlen($password) < 12) {
        $this->error('Mot de passe trop court : 12 caractères minimum.');

        return 1;
    }
    $user = User::updateOrCreate(['email' => strtolower($email)], [
        'name' => $name, 'password' => Hash::make($password), 'role' => $role, 'is_active' => true,
    ]);
    $this->info("Utilisateur prêt : {$user->email} ({$user->roleLabel()})");

    return 0;
})->purpose('Créer ou mettre à jour un utilisateur du registre EVC');

Artisan::command('evc:backup', function (BackupService $backups) {
    $file = $backups->create();
    $this->info('Sauvegarde créée : '.$file);
})->purpose('Sauvegarder la base et les PDF du registre');

Artisan::command('evc:restore {file} {--force}', function (string $file, BackupService $backups) {
    $path = is_file($file) ? $file : storage_path('app/private/backups/'.basename($file));
    if (! is_file($path)) {
        $this->error('Fichier introuvable : '.$file);

        return 1;
    }
    if (! $this->option('force') && ! $this->confirm('ATTENTION : le registre actuel sera entièrement remplacé. Continuer ?')) {
        return 1;
    }
    $backups->create(); // sauvegarde de sécurité de l'état actuel
    $backups->restore($path);
    $this->info('Registre restauré depuis '.basename($path));

    return 0;
})->purpose('Restaurer le registre depuis une sauvegarde');

Artisan::command('evc:reset-2fa {email}', function (string $email) {
    $user = User::where('email', strtolower($email))->firstOrFail();
    $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
    $this->info('Double authentification réinitialisée pour '.$user->email);
})->purpose('Réinitialiser la double authentification d’un utilisateur (téléphone perdu)');

// Tâche planifiée : sauvegarde chaque nuit à 2 h 30 (cron : * * * * * php artisan schedule:run)
Schedule::command('evc:backup')->dailyAt('02:30')->withoutOverlapping();
