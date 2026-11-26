<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * Sauvegarde complète du registre (base + PDF) dans une archive ZIP,
 * sans dépendre de mysqldump : compatible hébergement mutualisé.
 */
class BackupService
{
    public const TABLES = [
        'users', 'document_types', 'document_holders', 'official_documents', 'document_sequences',
        'document_approvals', 'document_audit_logs', 'document_revocations', 'verifications', 'site_visits',
    ];

    public function directory(): string
    {
        $dir = storage_path('app/private/backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    public function create(): string
    {
        $file = $this->directory().'/registre-evc-'.now()->format('Y-m-d_His').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de créer l’archive de sauvegarde.');
        }

        foreach (self::TABLES as $table) {
            $rows = DB::table($table)->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
            $zip->addFromString("database/{$table}.json", json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        $docs = storage_path('app/private/documents');
        foreach (File::exists($docs) ? File::files($docs) : [] as $f) {
            $zip->addFile($f->getPathname(), 'documents/'.$f->getFilename());
        }

        $zip->addFromString('manifest.json', json_encode([
            'application' => 'Registre EVC',
            'created_at' => now()->toIso8601String(),
            'tables' => self::TABLES,
        ], JSON_PRETTY_PRINT));
        $zip->close();

        $this->prune();

        return $file;
    }

    /** Restaure une sauvegarde : remplace TOUT le contenu actuel du registre. */
    public function restore(string $file): void
    {
        $zip = new ZipArchive;
        if ($zip->open($file) !== true || $zip->locateName('manifest.json') === false) {
            throw new RuntimeException('Archive de sauvegarde invalide.');
        }

        $data = [];
        foreach (self::TABLES as $table) {
            $json = $zip->getFromName("database/{$table}.json");
            if ($json === false) {
                throw new RuntimeException("Table manquante dans la sauvegarde : {$table}");
            }
            $data[$table] = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        }

        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($data) {
                foreach (array_reverse(self::TABLES) as $table) {
                    DB::table($table)->delete();
                }
                foreach (self::TABLES as $table) {
                    foreach (array_chunk($data[$table], 200) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $docs = storage_path('app/private/documents');
        File::ensureDirectoryExists($docs);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'documents/') && basename($name) !== '' && ! str_contains($name, '..')) {
                File::put($docs.'/'.basename($name), $zip->getFromIndex($i));
            }
        }
        $zip->close();
    }

    public function list(): array
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($f) => $f->getExtension() === 'zip')
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => ['name' => $f->getFilename(), 'size' => $f->getSize(), 'date' => $f->getMTime()])
            ->values()->all();
    }

    private function prune(): void
    {
        collect($this->list())->slice(config('evc.backups_keep', 30))
            ->each(fn ($b) => File::delete($this->directory().'/'.$b['name']));
    }
}
