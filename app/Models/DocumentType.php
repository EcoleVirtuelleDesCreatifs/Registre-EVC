<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DocumentType extends Model
{
    protected $fillable = ['code', 'label', 'ref_code', 'requires_formation', 'is_certificate', 'sort'];

    protected function casts(): array
    {
        return ['requires_formation' => 'boolean', 'is_certificate' => 'boolean'];
    }

    public static function ordered()
    {
        return Cache::remember('evc.document_types', 3600, fn () => static::orderBy('sort')->get());
    }

    /** Préfixe de référence, ex. EVC-DG ou EVC-ATT. */
    public function prefixFor(?string $formation): string
    {
        $code = $this->ref_code ?: ($formation ?: 'GEN');

        return 'EVC-'.strtoupper($code);
    }

    public function criteria(): array
    {
        return $this->is_certificate ? config('evc.criteres_certificat') : config('evc.criteres_autres');
    }
}
