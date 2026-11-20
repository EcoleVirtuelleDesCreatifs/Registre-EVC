<?php

namespace App\Services;

use App\Models\DocumentHolder;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use App\Models\User;
use App\Support\Reference;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Import Excel/CSV contrôlé ligne par ligne.
 * Colonnes reconnues (première ligne) : type ; formation ; titulaire ; promotion ; email ; matricule ;
 * date_delivrance ; reference ; note
 *
 * Modes :
 *  - brouillons  : crée des dossiers à faire valider (aucune référence attribuée)
 *  - historique  : reprend des documents déjà délivrés avec leur référence imprimée (publiés directement)
 */
class CsvImporter
{
    public const COLUMNS = ['type', 'formation', 'titulaire', 'promotion', 'email', 'matricule', 'date_delivrance', 'reference', 'note', 'civilite'];

    private const ALIASES = [
        'nom' => 'titulaire', 'nom_complet' => 'titulaire', 'beneficiaire' => 'titulaire', 'bénéficiaire' => 'titulaire',
        'date' => 'date_delivrance', 'date_de_delivrance' => 'date_delivrance', 'délivré_le' => 'date_delivrance',
        'référence' => 'reference', 'numero' => 'reference', 'numéro' => 'reference',
        'id_my_evc' => 'matricule', 'external_id' => 'matricule', 'type_document' => 'type',
    ];

    /** Analyse le fichier et renvoie chaque ligne avec ses erreurs et avertissements. */
    public function analyse(string $path, string $mode): array
    {
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // BOM Excel
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (count($lines) < 2) {
            throw new WorkflowException('Le fichier ne contient aucune ligne de données.');
        }

        $delimiter = substr_count($lines[0], ';') >= substr_count($lines[0], ',') ? ';' : ',';
        $headers = array_map(fn ($h) => $this->headerKey($h), str_getcsv($lines[0], $delimiter));

        if (! in_array('titulaire', $headers, true) || ! in_array('type', $headers, true)) {
            throw new WorkflowException('Colonnes obligatoires manquantes : « type » et « titulaire ».');
        }
        if ($mode === 'historique' && ! in_array('reference', $headers, true)) {
            throw new WorkflowException('Le mode historique exige une colonne « reference ».');
        }

        $types = DocumentType::all()->keyBy('code');
        $typeAliases = $types->mapWithKeys(fn ($t) => [Str::slug($t->label, '_') => $t->code, $t->code => $t->code])
            ->merge(['certificat_professionnel' => 'certificat', 'attestation' => 'attestation_formation', 'stage' => 'attestation_stage', 'lettre' => 'recommandation', 'recommandation' => 'recommandation']);
        $formations = array_keys(config('evc.formations'));

        $rows = [];
        $seenRefs = [];
        $seenKeys = [];

        foreach (array_slice($lines, 1) as $i => $line) {
            if (trim($line) === '' || trim($line, $delimiter.' ') === '') {
                continue;
            }
            $values = str_getcsv($line, $delimiter);
            $raw = [];
            foreach ($headers as $idx => $key) {
                if ($key) {
                    $raw[$key] = trim((string) ($values[$idx] ?? ''));
                }
            }

            $errors = [];
            $warnings = [];

            $typeCode = $typeAliases[Str::slug($raw['type'] ?? '', '_')] ?? null;
            $type = $typeCode ? $types[$typeCode] : null;
            if (! $type) {
                $errors[] = 'Type de document inconnu « '.($raw['type'] ?? '').' ».';
            }

            $name = DocumentHolder::normalizeName($raw['titulaire'] ?? '');
            if ($name === '') {
                $errors[] = 'Nom du titulaire manquant.';
            } elseif (mb_strlen($name) > 160) {
                $errors[] = 'Nom du titulaire trop long.';
            }

            $formation = strtoupper($raw['formation'] ?? '');
            $formation = $formation === '' ? null : $formation;
            if ($formation && ! in_array($formation, $formations, true)) {
                $errors[] = 'Formation inconnue « '.$formation.' » (attendu : '.implode(', ', $formations).').';
            }
            if ($type?->requires_formation && ! $formation) {
                $errors[] = 'Formation obligatoire pour ce type de document.';
            }

            $date = null;
            if (($raw['date_delivrance'] ?? '') !== '') {
                $date = $this->parseDate($raw['date_delivrance']);
                if (! $date) {
                    $errors[] = 'Date illisible « '.$raw['date_delivrance'].' » (format JJ/MM/AAAA ou AAAA-MM-JJ).';
                } elseif ($date->isFuture()) {
                    $errors[] = 'Date de délivrance dans le futur.';
                } elseif ($date->year < 2020) {
                    $errors[] = 'Date antérieure à la création de l’EVC.';
                }
            } elseif ($mode === 'historique') {
                $errors[] = 'Date de délivrance obligatoire en mode historique.';
            }

            $email = $raw['email'] ?? '';
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            }

            $reference = null;
            if ($mode === 'historique') {
                $reference = Reference::normalize($raw['reference'] ?? '');
                if (! Reference::isValid($reference)) {
                    $errors[] = 'Référence absente ou mal formée « '.($raw['reference'] ?? '').' ».';
                } elseif (isset($seenRefs[$reference])) {
                    $errors[] = 'Référence en double dans le fichier (ligne '.$seenRefs[$reference].').';
                } elseif (OfficialDocument::withTrashed()->where('reference', $reference)->exists()) {
                    $errors[] = 'Référence déjà présente dans le registre.';
                } elseif ($date && ! str_contains($reference, '-'.$date->year.'-')) {
                    $warnings[] = 'L’année de la référence ne correspond pas à la date de délivrance.';
                }
                $seenRefs[$reference] = $i + 2;
            }

            // Bénéficiaire existant ?
            $matricule = $raw['matricule'] ?? '';
            $holder = null;
            if ($matricule !== '') {
                $holder = DocumentHolder::where('external_id', $matricule)->first();
            }
            if (! $holder && $name !== '') {
                $holder = DocumentHolder::where('full_name', $name)->first();
                if ($holder && $matricule !== '' && $holder->external_id && $holder->external_id !== $matricule) {
                    $warnings[] = 'Un titulaire du même nom existe avec un autre matricule : un nouveau titulaire sera créé.';
                    $holder = null;
                }
            }
            if ($holder) {
                $warnings[] = 'Titulaire déjà enregistré : le document lui sera rattaché.';
            }

            // Doublon de dossier (même titulaire, même type, même formation)
            $key = mb_strtolower($name).'|'.($type?->code).'|'.$formation;
            if (isset($seenKeys[$key])) {
                $errors[] = 'Même titulaire, même document et même formation qu’à la ligne '.$seenKeys[$key].'.';
            } elseif ($holder && $type && OfficialDocument::where('holder_id', $holder->id)
                ->where('document_type_id', $type->id)->where('formation', $formation)
                ->whereNotIn('status', ['rejete', 'revoque'])->exists()) {
                $errors[] = 'Ce titulaire possède déjà ce document dans le registre (doublon).';
            }
            $seenKeys[$key] = $i + 2;

            $rows[] = [
                'line' => $i + 2,
                'data' => [
                    'type' => $type?->code,
                    'formation' => $formation,
                    'titulaire' => $name,
                    'promotion' => $raw['promotion'] ?? null,
                    'email' => $email ?: null,
                    'matricule' => $matricule ?: null,
                    'date' => $date?->toDateString(),
                    'reference' => $reference,
                    'note' => $raw['note'] ?? null,
                    'civilite' => $this->civility($raw['civilite'] ?? null),
                    'holder_id' => $holder?->id,
                ],
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        return $rows;
    }

    /** Intègre uniquement les lignes sans erreur. Renvoie le nombre de documents créés. */
    public function import(array $rows, string $mode, User $user, ReferenceGenerator $references): int
    {
        $types = DocumentType::all()->keyBy('code');
        $count = 0;

        DB::transaction(function () use ($rows, $mode, $user, $types, &$count) {
            foreach ($rows as $row) {
                if ($row['errors']) {
                    continue;
                }
                $d = $row['data'];

                $holder = $d['holder_id'] ? DocumentHolder::find($d['holder_id']) : null;
                if (! $holder) {
                    $holder = DocumentHolder::create([
                        'full_name' => $d['titulaire'],
                        'email' => $d['email'],
                        'promotion' => $d['promotion'],
                        'external_id' => $d['matricule'],
                    ]);
                    AuditLogger::log('titulaire_cree', 'Titulaire créé par import', null, $holder);
                }

                $historique = $mode === 'historique';
                $doc = OfficialDocument::create([
                    'reference' => $historique ? $d['reference'] : null,
                    'public_token' => $historique ? Str::random(40) : null,
                    'document_type_id' => $types[$d['type']]->id,
                    'holder_id' => $holder->id,
                    'formation' => $d['formation'],
                    'promotion' => $d['promotion'],
                    'issued_at' => $d['date'],
                    'status' => $historique ? 'publie' : 'brouillon',
                    'published_at' => $historique ? now() : null,
                    'published_by' => $historique ? $user->id : null,
                    'is_legacy' => $historique,
                    'internal_note' => $d['note'],
                    'details' => ($d['civilite'] ?? null) ? ['civilite' => $d['civilite']] : null,
                    'source' => $historique ? 'historique' : 'import',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
                AuditLogger::log('import', ($historique ? 'Reprise historique ' : 'Brouillon importé ').'(ligne '.$row['line'].')', $doc);
                $count++;
            }
        });

        if ($mode === 'historique') {
            $references->resync();
        }

        return $count;
    }

    private function headerKey(string $header): ?string
    {
        $key = Str::of($header)->trim()->lower()->replace([' ', '-'], '_')->value();
        $key = self::ALIASES[$key] ?? Str::slug($key, '_');
        $key = self::ALIASES[$key] ?? $key;

        return in_array($key, self::COLUMNS, true) ? $key : null;
    }

    private function parseDate(string $value): ?Carbon
    {
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, trim($value));
                if ($date && $date->format($format) === trim($value)) {
                    return $date;
                }
            } catch (Throwable) {
            }
        }
        if (is_numeric($value) && (int) $value > 40000) { // date Excel
            return Carbon::create(1899, 12, 30)->addDays((int) $value);
        }

        return null;
    }

    /** « Mme », « Madame », « F » → Mme ; « M », « M. », « Monsieur », « H » → M. */
    private function civility(?string $value): ?string
    {
        $v = mb_strtolower(trim((string) $value), 'UTF-8');
        if ($v === '') {
            return null;
        }

        return in_array($v, ['mme', 'madame', 'f', 'mlle', 'mademoiselle'], true) ? 'Mme' : 'M';
    }
}
