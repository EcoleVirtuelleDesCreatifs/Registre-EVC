<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Crée le premier compte Direction générale à partir des variables ADMIN_* du .env. */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('evc.admin.email');
        $password = config('evc.admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD absents du .env : aucun compte créé. Utilisez : php artisan evc:user "Nom" email direction_generale');

            return;
        }
        if (strlen($password) < 12) {
            $this->command?->error('ADMIN_PASSWORD doit contenir au moins 12 caractères.');

            return;
        }

        User::updateOrCreate(
            ['email' => strtolower($email)],
            ['name' => config('evc.admin.name'), 'password' => $password, 'role' => 'direction_generale', 'is_active' => true]
        );

        $this->command?->info("Compte Direction générale : {$email} (la double authentification sera demandée à la première connexion).");
    }
}
