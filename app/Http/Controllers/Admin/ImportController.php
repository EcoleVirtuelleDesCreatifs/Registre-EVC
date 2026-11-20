<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\CsvImporter;
use App\Services\ReferenceGenerator;
use App\Services\WorkflowException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function create()
    {
        Gate::authorize('imports.drafts');

        return view('admin.imports.create');
    }

    /** Étape 1 : contrôle de chaque ligne, rien n'est encore enregistré. */
    public function analyse(Request $request, CsvImporter $importer)
    {
        Gate::authorize('imports.drafts');
        $request->validate([
            'fichier' => ['required', 'file', 'mimes:csv,txt', 'max:'.config('evc.max_import_kb')],
            'mode' => ['required', 'in:brouillons,historique'],
        ], ['fichier.mimes' => 'Enregistrez votre tableau Excel au format « CSV UTF-8 (délimité par des points-virgules) ».']);

        $mode = $request->input('mode');
        if ($mode === 'historique') {
            Gate::authorize('imports.history');
        }

        try {
            $rows = $importer->analyse($request->file('fichier')->getRealPath(), $mode);
        } catch (WorkflowException $e) {
            return back()->withErrors(['fichier' => $e->getMessage()]);
        }

        $token = Str::random(32);
        Storage::disk('local')->put("imports/{$token}.json", json_encode(['mode' => $mode, 'user' => $request->user()->id, 'rows' => $rows]));

        return view('admin.imports.preview', compact('rows', 'mode', 'token'));
    }

    /** Étape 2 : intégration des seules lignes valides. */
    public function store(Request $request, CsvImporter $importer, ReferenceGenerator $references)
    {
        Gate::authorize('imports.drafts');
        $request->validate(['token' => ['required', 'alpha_num', 'size:32']]);
        $path = 'imports/'.$request->input('token').'.json';

        abort_unless(Storage::disk('local')->exists($path), 404, 'Analyse expirée : recommencez l’import.');
        $payload = json_decode(Storage::disk('local')->get($path), true);
        abort_unless(($payload['user'] ?? null) === $request->user()->id, 403);
        if ($payload['mode'] === 'historique') {
            Gate::authorize('imports.history');
        }

        $count = $importer->import($payload['rows'], $payload['mode'], $request->user(), $references);
        Storage::disk('local')->delete($path);
        AuditLogger::log('import_termine', "Import {$payload['mode']} : {$count} document(s)");

        return redirect()->route('admin.documents.index')->with('status', "{$count} document(s) importé(s).");
    }

    public function template()
    {
        Gate::authorize('imports.drafts');
        $csv = "\xEF\xBB\xBF".implode(';', CsvImporter::COLUMNS)."\n"
            ."certificat;DG;Nom Prénoms;Promo 2026;email@exemple.com;MAT-0001;13/11/2026;;;M\n"
            ."attestation_formation;CM;Nom Prénoms;Promo 2026;;;;;Formation en cours;Mme\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele-import-registre-evc.csv"',
        ]);
    }
}
