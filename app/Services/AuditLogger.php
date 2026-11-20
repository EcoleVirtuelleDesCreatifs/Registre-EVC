<?php

namespace App\Services;

use App\Models\DocumentAuditLog;
use App\Models\DocumentHolder;
use App\Models\OfficialDocument;

class AuditLogger
{
    public static function log(
        string $action,
        ?string $description = null,
        ?OfficialDocument $document = null,
        ?DocumentHolder $holder = null,
        ?array $changes = null,
        ?int $userId = null,
    ): void {
        DocumentAuditLog::create([
            'action' => $action,
            'description' => $description ? mb_substr($description, 0, 250) : null,
            'document_id' => $document?->id,
            'holder_id' => $holder?->id ?? $document?->holder_id,
            'user_id' => $userId ?? auth()->id(),
            'changes' => $changes ?: null,
            'ip' => request()?->ip(),
        ]);
    }

    /** Différences lisibles entre deux états d'un modèle (champs sensibles exclus). */
    public static function diff(array $before, array $after): array
    {
        $ignore = ['updated_at', 'created_at', 'updated_by', 'password', 'two_factor_secret', 'remember_token'];
        $out = [];
        foreach ($after as $key => $value) {
            if (in_array($key, $ignore, true)) {
                continue;
            }
            $old = $before[$key] ?? null;
            if ($old != $value) {
                $out[$key] = ['avant' => $old, 'apres' => $value];
            }
        }

        return $out;
    }
}
