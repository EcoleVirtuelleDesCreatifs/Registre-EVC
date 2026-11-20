<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** Vérifie une permission définie dans config/evc.php. */
    public function hasPermission(string $permission): bool
    {
        return $this->is_active && in_array($this->role, (config('evc.permissions')[$permission] ?? []), true);
    }

    public function roleLabel(): string
    {
        return config("evc.roles.{$this->role}", $this->role);
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function mustUseTwoFactor(): bool
    {
        return in_array($this->role, config('evc.two_factor_required_roles', []), true);
    }
}
