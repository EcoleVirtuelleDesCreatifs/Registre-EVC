<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApproval extends Model
{
    public const UPDATED_AT = null;

    public const STEPS = [
        'soumission' => 'Soumission au contrôle',
        'controle_pedagogique' => 'Contrôle pédagogique',
        'approbation' => 'Approbation',
        'delivrance' => 'Délivrance',
        'publication' => 'Publication',
        'reouverture' => 'Réouverture du dossier',
    ];

    protected $fillable = ['document_id', 'step', 'decision', 'comment', 'checklist', 'user_id'];

    protected function casts(): array
    {
        return ['checklist' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stepLabel(): string
    {
        return self::STEPS[$this->step] ?? $this->step;
    }
}
