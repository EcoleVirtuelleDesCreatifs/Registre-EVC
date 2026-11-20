<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentHolder extends Model
{
    protected $fillable = ['full_name', 'email', 'phone', 'birth_date', 'promotion', 'external_id', 'notes'];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OfficialDocument::class, 'holder_id');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        return $q->where(fn ($w) => $w->where('full_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('external_id', $term));
    }

    public static function normalizeName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }
}
