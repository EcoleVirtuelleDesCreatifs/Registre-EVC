<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Journal d'audit : en écriture seule. Aucune ligne ne peut être modifiée ou supprimée
 * depuis l'application.
 */
class DocumentAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['document_id', 'holder_id', 'user_id', 'action', 'description', 'changes', 'ip'];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Le journal d’audit ne peut pas être modifié.'));
        static::deleting(fn () => throw new LogicException('Le journal d’audit ne peut pas être supprimé.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(OfficialDocument::class, 'document_id')->withTrashed();
    }
}
