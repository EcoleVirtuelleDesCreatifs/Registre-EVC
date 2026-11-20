<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use Illuminate\Support\Facades\DB;

/**
 * Numérotation unique et transactionnelle : EVC-DG-2026-0007, EVC-ATT-2026-0001…
 * Un numéro attribué n'est jamais réutilisé (la séquence ne recule pas, même après révocation).
 */
class ReferenceGenerator
{
    /** Anciens préfixes (documents déjà imprimés) dont la numérotation se poursuit. */
    public const LEGACY_PREFIXES = [
        'EVC-ATT' => 'ATT-EVC',
        'EVC-STG' => 'STG-EVC',
        'EVC-REC' => 'REC-EVC',
    ];

    /** Attribue le numéro suivant. À appeler à l'intérieur d'une transaction. */
    public function next(DocumentType $type, ?string $formation, int $year): string
    {
        $prefix = $type->prefixFor($formation);

        return DB::transaction(function () use ($prefix, $year) {
            DocumentSequence::query()->insertOrIgnore([
                'prefix' => $prefix,
                'year' => $year,
                'last_number' => $this->highestExisting($prefix, $year),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DocumentSequence::where('prefix', $prefix)->where('year', $year)->lockForUpdate()->firstOrFail();

            do {
                $sequence->last_number++;
                $reference = sprintf('%s-%d-%04d', $prefix, $year, $sequence->last_number);
            } while (OfficialDocument::withTrashed()->where('reference', $reference)->exists());

            $sequence->save();

            return $reference;
        });
    }

    /** Aperçu du prochain numéro, sans le réserver. */
    public function peek(DocumentType $type, ?string $formation, int $year): string
    {
        $prefix = $type->prefixFor($formation);
        $last = DocumentSequence::where('prefix', $prefix)->where('year', $year)->value('last_number')
            ?? $this->highestExisting($prefix, $year);

        return sprintf('%s-%d-%04d', $prefix, $year, $last + 1);
    }

    /** Plus grand numéro déjà présent dans le registre pour ce préfixe (et son ancien format). */
    public function highestExisting(string $prefix, int $year): int
    {
        $prefixes = array_filter([$prefix, self::LEGACY_PREFIXES[$prefix] ?? null]);
        $max = 0;

        foreach ($prefixes as $p) {
            $start = "{$p}-{$year}-";
            OfficialDocument::withTrashed()->where('reference', 'like', $start.'%')->pluck('reference')
                ->each(function ($ref) use (&$max, $start) {
                    $max = max($max, (int) substr($ref, strlen($start)));
                });
        }

        return $max;
    }

    /** Recale les séquences après un import historique. */
    public function resync(): void
    {
        DocumentSequence::query()->get()->each(function (DocumentSequence $s) {
            $s->update(['last_number' => max($s->last_number, $this->highestExisting($s->prefix, $s->year))]);
        });
    }
}
