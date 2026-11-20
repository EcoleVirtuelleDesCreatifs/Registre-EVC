<?php

namespace Database\Seeders;

use App\Models\DocumentHolder;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use App\Services\AuditLogger;
use App\Services\ReferenceGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reprise des documents déjà délivrés par l'EVC.
 * Les références déjà imprimées sont conservées telles quelles (ATT-EVC, STG-EVC, REC-EVC…) ;
 * la numérotation des nouveaux documents (EVC-ATT, EVC-STG, EVC-REC) continue à partir d'elles.
 */
class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        // [référence, type, formation, titulaire, date, statut, note interne]
        $documents = [
            ['EVC-CM-2026-0002', 'certificat', 'CM', 'Salomon Kieno', '2026-06-20', 'publie', null],
            ['EVC-CM-2026-0003', 'certificat', 'CM', 'Afi Constance Sitsofé Ayim', '2026-11-13', 'publie', null],
            ['EVC-DG-2026-0001', 'certificat', 'DG', 'Afi Constance Sitsofé Ayim', '2026-11-13', 'publie', null],
            ['EVC-DG-2026-0002', 'certificat', 'DG', 'Jean Molou Pivi', '2026-11-13', 'publie', null],
            ['EVC-DG-2026-0003', 'certificat', 'DG', 'Yao Désiré Aimé Avonyo', '2026-11-13', 'publie', null],
            ['EVC-DG-2026-0004', 'certificat', 'DG', 'Jean-Yves Roland N’Cho', '2026-11-13', 'publie', 'Formation à confirmer (Design par défaut).'],
            ['EVC-DG-2026-0005', 'certificat', 'DG', 'Mourifidate Eunice Mama', '2026-11-13', 'en_verification', 'Formation en cours de finalisation : à contrôler puis approuver après avis du jury. Non visible publiquement avant publication.'],
            ['ATT-EVC-2026-0001', 'attestation_formation', 'DG', 'Mourifidate Eunice Mama', '2026-11-13', 'publie', 'Inscrite le 11/02/2026, formation en cours de finalisation.'],
            ['STG-EVC-2026-0001', 'attestation_stage', null, 'Jean Molou Pivi', '2026-11-13', 'publie', 'Stage du 21/03/2026 au 21/07/2026. PDF à remplacer par la version signée.'],
            ['REC-EVC-2026-0002', 'recommandation', 'CM', 'Salomon Kieno', '2026-06-20', 'publie', 'Signée par Amandine Kra.'],
            ['REC-EVC-2026-0003', 'recommandation', 'DG', 'Afi Constance Sitsofé Ayim', '2026-11-13', 'publie', 'Signée par Bossombra Bile. PDF à remplacer par la version signée.'],
            ['REC-EVC-2026-0004', 'recommandation', 'DG', 'Jean Molou Pivi', '2026-11-13', 'publie', 'Signée par Bossombra Bile. PDF à remplacer par la version signée.'],
        ];

        $types = DocumentType::all()->keyBy('code');
        $source = database_path('seeders/files');
        $created = 0;

        DB::transaction(function () use ($documents, $types, $source, &$created) {
            foreach ($documents as [$reference, $typeCode, $formation, $name, $date, $status, $note]) {
                if (OfficialDocument::withTrashed()->where('reference', $reference)->exists()) {
                    continue; // déjà repris : on ne touche à rien
                }

                $holder = DocumentHolder::firstOrCreate(['full_name' => $name]);

                $doc = OfficialDocument::create([
                    'reference' => $reference,
                    'public_token' => Str::random(40),
                    'document_type_id' => $types[$typeCode]->id,
                    'holder_id' => $holder->id,
                    'formation' => $formation,
                    'issued_at' => $date,
                    'status' => $status,
                    'pdf_public' => true,
                    'is_legacy' => true,
                    'source' => 'historique',
                    'internal_note' => $note,
                    'published_at' => $status === 'publie' ? $date : null,
                ]);

                foreach (['pdf' => 'pdf_path', 'jpg' => 'preview_path'] as $ext => $column) {
                    $file = "{$source}/{$reference}.{$ext}";
                    if (File::exists($file)) {
                        $path = "documents/{$reference}-origine.{$ext}";
                        Storage::disk('local')->put($path, File::get($file));
                        $doc->update(array_filter([$column => $path, 'pdf_sha256' => $ext === 'pdf' ? hash_file('sha256', $file) : null]));
                    }
                }

                AuditLogger::log('reprise_historique', "Reprise du document déjà délivré {$reference}", $doc, $holder);
                $created++;
            }
        });

        app(ReferenceGenerator::class)->resync();

        $this->command?->info("{$created} document(s) historique(s) repris. Séquences de numérotation synchronisées.");
    }
}
