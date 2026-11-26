<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentHolder;
use App\Models\OfficialDocument;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HolderController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('documents.view');

        if ($request->wantsJson()) { // recherche rapide depuis le formulaire de document
            return DocumentHolder::search($request->input('q'))->orderBy('full_name')->limit(15)
                ->get(['id', 'full_name', 'promotion', 'external_id']);
        }

        return view('admin.holders.index', [
            'holders' => DocumentHolder::search($request->input('q'))->withCount(['documents',
                'documents as blocking_count' => fn ($q) => $q->withTrashed()->where(fn ($w) => $w->whereNotNull('reference')->orWhere('status', '!=', 'brouillon'))])
                ->orderBy('full_name')->paginate(30)->withQueryString(),
        ]);
    }

    public function create()
    {
        Gate::authorize('holders.manage');

        return view('admin.holders.form', ['holder' => new DocumentHolder]);
    }

    public function store(Request $request)
    {
        Gate::authorize('holders.manage');
        $data = $this->validated($request);
        $data['full_name'] = DocumentHolder::normalizeName($data['full_name']);

        if (DocumentHolder::where('full_name', $data['full_name'])->exists() && ! $request->boolean('confirm_duplicate')) {
            return back()->withInput()->withErrors(['full_name' => 'Un titulaire porte déjà ce nom. Vérifiez qu’il ne s’agit pas de la même personne, puis cochez « Créer quand même ».'])
                ->with('ask_duplicate', true);
        }

        $holder = DocumentHolder::create($data);
        AuditLogger::log('titulaire_cree', 'Titulaire créé : '.$holder->full_name, null, $holder);

        return redirect()->route('admin.beneficiaires.show', $holder)->with('status', 'Titulaire enregistré.');
    }

    public function show(DocumentHolder $holder)
    {
        Gate::authorize('documents.view');

        return view('admin.holders.show', ['holder' => $holder->load(['documents.type'])]);
    }

    public function edit(DocumentHolder $holder)
    {
        Gate::authorize('holders.manage');

        return view('admin.holders.form', compact('holder'));
    }

    public function update(Request $request, DocumentHolder $holder)
    {
        Gate::authorize('holders.manage');
        $before = $holder->getAttributes();
        $data = $this->validated($request, $holder);
        $data['full_name'] = DocumentHolder::normalizeName($data['full_name']);

        // Le nom d'un titulaire dont un document est publié ne change qu'avec une justification
        if ($data['full_name'] !== $holder->full_name
            && $holder->documents()->whereIn('status', ['publie', 'revoque'])->exists()
            && blank($request->input('justification'))) {
            return back()->withInput()->withErrors(['justification' => 'Ce titulaire a des documents publiés : indiquez la justification du changement de nom.']);
        }

        $holder->update($data);
        AuditLogger::log('titulaire_modifie', 'Titulaire modifié'.($request->filled('justification') ? ' : '.$request->input('justification') : ''),
            null, $holder, AuditLogger::diff($before, $holder->getAttributes()));

        return redirect()->route('admin.beneficiaires.show', $holder)->with('status', 'Titulaire mis à jour.');
    }

    public function destroy(DocumentHolder $holder)
    {
        Gate::authorize('holders.manage');

        // Bloquant : tout document engagé dans la délivrance (référence attribuée ou statut au-delà du brouillon)
        $docs = $holder->documents()->withTrashed()->get();
        $blocking = $docs->filter(fn (OfficialDocument $d) => $d->reference || $d->status !== 'brouillon');
        if ($blocking->isNotEmpty()) {
            return back()->withErrors(['holder' => 'Impossible de supprimer '.$holder->full_name.' : '
                .$blocking->count().' document(s) engagé(s) dans la délivrance ('
                .$blocking->map(fn ($d) => $d->reference ?? config("evc.statuses.{$d->status}", $d->status))->implode(', ')
                .'). Un document délivré ne se supprime pas : il se révoque depuis son dossier.']);
        }
        if ($docs->whereNull('deleted_at')->isNotEmpty()) {
            Gate::authorize('documents.edit');
        }

        $name = $holder->full_name;
        DB::transaction(function () use ($holder, $docs) {
            // Les brouillons (actifs ou déjà supprimés) partent avec le titulaire
            foreach ($docs as $doc) {
                AuditLogger::log('suppression_brouillon', 'Brouillon supprimé avec le titulaire '.$holder->full_name, $doc);
                $doc->forceDelete();
            }
            $holder->delete();
        });
        AuditLogger::log('titulaire_supprime', 'Titulaire supprimé : '.$name.($docs->count() ? ' (avec '.$docs->count().' brouillon(s))' : ''));

        return redirect()->route('admin.beneficiaires.index')->with('status', 'Titulaire supprimé : '.$name.'.');
    }

    private function validated(Request $request, ?DocumentHolder $holder = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'promotion' => ['nullable', 'string', 'max:40'],
            'external_id' => ['nullable', 'string', 'max:80', Rule::unique('document_holders', 'external_id')->ignore($holder?->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['external_id.unique' => 'Ce matricule My EVC est déjà attribué.']);
    }
}
