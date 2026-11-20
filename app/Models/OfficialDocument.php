<?php

namespace App\Models;

use App\Support\Reference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfficialDocument extends Model
{
    use SoftDeletes;

    /** Statuts dans lesquels le dossier peut encore être modifié librement. */
    public const EDITABLE = ['brouillon', 'rejete'];

    protected $fillable = [
        'reference', 'public_token', 'document_type_id', 'holder_id', 'formation', 'promotion',
        'issued_at', 'status', 'pdf_path', 'preview_path', 'pdf_public', 'checklist', 'details',
        'pdf_sha256', 'generated_pdf_path', 'generated_sha256', 'generated_at', 'template_version',
        'pedagogical_validated_at', 'pedagogical_validated_by', 'approved_at', 'approved_by',
        'published_at', 'published_by', 'is_legacy', 'internal_note', 'source', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'pdf_public' => 'boolean',
            'is_legacy' => 'boolean',
            'checklist' => 'array',
            'details' => 'array',
            'generated_at' => 'datetime',
            'pedagogical_validated_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(DocumentHolder::class, 'holder_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'document_id')->latest('id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(DocumentAuditLog::class, 'document_id')->latest('id');
    }

    public function revocation(): HasOne
    {
        return $this->hasOne(DocumentRevocation::class, 'document_id')->latestOfMany();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['q'] ?? null, function ($q, $term) {
                $ref = Reference::normalize($term);
                $q->where(fn ($w) => $w->where('reference', 'like', "%{$ref}%")
                    ->orWhereHas('holder', fn ($h) => $h->where('full_name', 'like', "%{$term}%")));
            })
            ->when($f['type'] ?? null, fn ($q, $v) => $q->whereHas('type', fn ($t) => $t->where('code', $v)))
            ->when($f['formation'] ?? null, fn ($q, $v) => $q->where('formation', $v))
            ->when($f['promotion'] ?? null, fn ($q, $v) => $q->where('promotion', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('issued_at', '<=', $v));
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE, true);
    }

    public function isPublic(): bool
    {
        return in_array($this->status, ['publie', 'revoque'], true) && $this->reference !== null;
    }

    public function statusLabel(): string
    {
        return config("evc.statuses.{$this->status}", $this->status);
    }

    public function formationLabel(): ?string
    {
        return $this->formation ? config("evc.formations.{$this->formation}", $this->formation) : null;
    }

    public function issuedFr(): string
    {
        return $this->issued_at?->locale('fr')->translatedFormat('j F Y') ?? '—';
    }

    /** Document produit automatiquement par le registre (et non repris de l'historique). */
    public function isGenerable(): bool
    {
        return ! $this->is_legacy;
    }

    /** Valeur d'un champ de contenu, avec valeur par défaut. */
    public function detail(string $key, mixed $default = null): mixed
    {
        $value = ($this->details ?? [])[$key] ?? null;

        return blank($value) ? $default : $value;
    }

    public function checklistComplete(): bool
    {
        $criteria = array_keys($this->type?->criteria() ?? []);
        $done = $this->checklist ?? [];

        return $criteria !== [] && collect($criteria)->every(fn ($c) => ! empty($done[$c]));
    }
}
