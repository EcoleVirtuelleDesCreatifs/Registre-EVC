<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentHolder;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use App\Services\AuditLogger;
use App\Services\DocumentRenderer;
use App\Services\ReferenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    private const FILTERS = ['q', 'type', 'formation', 'promotion', 'status', 'from', 'to'];

    public function index(Request $request)
    {
        Gate::authorize('viewAny', OfficialDocument::class);

        $documents = OfficialDocument::with(['type', 'holder'])
            ->filter($request->only(self::FILTERS))
            ->orderByRaw('issued_at is null desc')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $promotions = OfficialDocument::whereNotNull('promotion')->distinct()->orderBy('promotion')->pluck('promotion');

        return view('admin.documents.index', [
            'documents' => $documents,
            'types' => DocumentType::ordered(),
            'promotions' => $promotions,
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('create', OfficialDocument::class);

        return view('admin.documents.form', [
            'document' => new OfficialDocument(['formation' => 'DG']),
            'types' => DocumentType::ordered(),
            'holder' => $request->filled('beneficiaire') ? DocumentHolder::find($request->integer('beneficiaire')) : null,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', OfficialDocument::class);
        $data = $this->validated($request, true);

        $doc = DB::transaction(function () use ($data, $request) {
            if (! ($data['holder_id'] ?? null) && blank($data['holder_name'] ?? null)) {
                // Communiqué officiel : pas de titulaire, on rattache à l'établissement.
                $holder = DocumentHolder::firstOrCreate(
                    ['full_name' => 'École Virtuelle des Créatifs'],
                    ['notes' => 'Titulaire générique pour les communiqués officiels']
                );
            } else {
                $holder = $data['holder_id']
                    ? DocumentHolder::findOrFail($data['holder_id'])
                    : DocumentHolder::create([
                        'full_name' => DocumentHolder::normalizeName($data['holder_name']),
                        'email' => $data['holder_email'] ?? null,
                        'promotion' => $data['promotion'] ?? null,
                        'external_id' => $data['holder_external_id'] ?? null,
                    ]);
            }
            if (! ($data['holder_id'] ?? null) && filled($data['holder_name'] ?? null)) {
                AuditLogger::log('titulaire_cree', 'Titulaire créé : '.$holder->full_name, null, $holder);
            }

            $doc = OfficialDocument::create([
                'document_type_id' => $data['document_type_id'],
                'holder_id' => $holder->id,
                'formation' => $data['formation'] ?? null,
                'promotion' => $data['promotion'] ?? $holder->promotion,
                'pdf_public' => $request->boolean('pdf_public', true),
                'internal_note' => $data['internal_note'] ?? null,
                'details' => $this->details($request),
                'status' => 'brouillon',
                'source' => 'manuel',
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            AuditLogger::log('creation', 'Dossier créé (brouillon)', $doc);

            return $doc;
        });

        return redirect()->route('admin.documents.show', $doc)
            ->with('status', 'Dossier créé en brouillon. Soumettez-le au contrôle quand il est complet.');
    }

    public function show(OfficialDocument $document)
    {
        Gate::authorize('view', $document);
        $document->load(['type', 'holder', 'approvals.user', 'revocation.user', 'creator']);

        return view('admin.documents.show', [
            'document' => $document,
            'logs' => $document->auditLogs()->with('user')->limit(30)->get(),
            'verifications' => $document->reference
                ? \App\Models\Verification::where('document_id', $document->id)->count() : 0,
        ]);
    }

    public function edit(OfficialDocument $document)
    {
        Gate::authorize('update', $document);

        return view('admin.documents.form', [
            'document' => $document->load('holder'),
            'types' => DocumentType::ordered(),
            'holder' => $document->holder,
        ]);
    }

    public function update(Request $request, OfficialDocument $document)
    {
        Gate::authorize('update', $document);
        $data = $this->validated($request, false);
        $before = $document->getAttributes();

        $document->update([
            'document_type_id' => $data['document_type_id'],
            'holder_id' => $data['holder_id'] ?? $document->holder_id,
            'formation' => $data['formation'] ?? null,
            'promotion' => $data['promotion'] ?? null,
            'pdf_public' => $request->boolean('pdf_public'),
            'internal_note' => $data['internal_note'] ?? null,
            'details' => $this->details($request),
            'updated_by' => $request->user()->id,
        ]);
        AuditLogger::log('modification', 'Dossier modifié', $document, null, AuditLogger::diff($before, $document->getAttributes()));

        return redirect()->route('admin.documents.show', $document)->with('status', 'Dossier mis à jour.');
    }

    /** Active/désactive la consultation en ligne du PDF sur la page de vérification publique. */
    public function togglePublic(OfficialDocument $document)
    {
        Gate::authorize('togglePublic', $document);

        $document->update(['pdf_public' => ! $document->pdf_public]);
        AuditLogger::log('consultation_'.($document->pdf_public ? 'activee' : 'desactivee'),
            $document->pdf_public ? 'Consultation en ligne du PDF activée' : 'Consultation en ligne du PDF désactivée', $document);

        return back()->with('status', $document->pdf_public
            ? 'Consultation en ligne activée : le PDF est visible après vérification publique.'
            : 'Consultation en ligne désactivée : seul le statut du document reste vérifiable.');
    }

    public function destroy(OfficialDocument $document)
    {
        Gate::authorize('delete', $document);
        AuditLogger::log('suppression_brouillon', 'Brouillon supprimé', $document);
        $document->delete(); // suppression douce : la ligne reste en base

        return redirect()->route('admin.documents.index')->with('status', 'Brouillon supprimé.');
    }

    /** Joindre le PDF signé et/ou l'aperçu. */
    public function attach(Request $request, OfficialDocument $document)
    {
        Gate::authorize('attach', $document);
        $request->validate([
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.config('evc.max_pdf_kb')],
            'apercu' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('evc.max_image_kb')],
        ]);
        if (! $request->hasFile('pdf') && ! $request->hasFile('apercu')) {
            return back()->withErrors(['pdf' => 'Choisissez un fichier.']);
        }

        foreach (['pdf' => 'pdf_path', 'apercu' => 'preview_path'] as $input => $column) {
            if ($file = $request->file($input)) {
                $old = $document->{$column};
                $name = ($document->reference ?? 'dossier-'.$document->id).'-'.Str::lower(Str::random(10)).'.'.$file->extension();
                $document->update(array_filter([
                    $column => $file->storeAs('documents', $name, 'local'),
                    'pdf_sha256' => $input === 'pdf' ? hash_file('sha256', $file->getRealPath()) : null,
                    'updated_by' => $request->user()->id,
                ]));
                // L'ancien fichier est conservé pour la traçabilité (non supprimé)
                AuditLogger::log($input === 'pdf' ? 'pdf_joint' : 'apercu_joint',
                    ($old ? 'Fichier remplacé' : 'Fichier joint').' : '.$input, $document, null, ['ancien' => $old, 'nouveau' => $document->{$column}]);
            }
        }

        return back()->with('status', 'Fichier enregistré.');
    }

    public function file(OfficialDocument $document, string $kind)
    {
        Gate::authorize('view', $document);
        $path = $kind === 'apercu' ? $document->preview_path : $document->pdf_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function nextReference(Request $request, ReferenceGenerator $references)
    {
        Gate::authorize('viewAny', OfficialDocument::class);
        $type = DocumentType::find($request->integer('type'));
        if (! $type) {
            return response()->json(['reference' => null]);
        }

        return response()->json([
            'reference' => $references->peek($type, $request->input('formation') ?: null, (int) now()->format('Y')),
        ]);
    }

    /** Export CSV (Excel) de la liste filtrée. */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', OfficialDocument::class);
        $query = OfficialDocument::with(['type', 'holder'])->filter($request->only(self::FILTERS))->orderBy('id');
        AuditLogger::log('export', 'Export CSV du registre');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['reference', 'type', 'formation', 'titulaire', 'promotion', 'date_delivrance', 'statut', 'pdf_joint'], ';');
            $query->chunk(500, function ($docs) use ($out) {
                foreach ($docs as $d) {
                    fputcsv($out, [$d->reference, $d->type->label, $d->formationLabel(), $d->holder->full_name,
                        $d->promotion, $d->issued_at?->format('d/m/Y'), $d->statusLabel(), $d->pdf_path ? 'oui' : 'non'], ';');
                }
            });
            fclose($out);
        }, 'registre-evc-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Aperçu du document généré (HTML) ou PDF (spécimen tant que la référence n'est pas attribuée). */
    public function render(Request $request, OfficialDocument $document, DocumentRenderer $renderer)
    {
        Gate::authorize('view', $document);
        abort_if($document->is_legacy, 404, 'Document historique : consultez son PDF d’origine.');

        if ($request->query('format') === 'pdf') {
            $name = ($document->reference ?? 'apercu-dossier-'.$document->id).'.pdf';

            return response($renderer->pdf($document), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$name.'"',
                'Cache-Control' => 'no-store, private',
            ]);
        }

        return response($renderer->html($document, false, null, $request->boolean('embed')));
    }

    /** Régénère le PDF officiel (avant publication uniquement), par exemple après correction d'un contenu. */
    public function regenerate(Request $request, OfficialDocument $document, DocumentRenderer $renderer)
    {
        Gate::authorize('generate', $document);
        try {
            $renderer->generate($document, $request->user());
        } catch (\App\Services\WorkflowException $e) {
            return back()->withErrors(['workflow' => $e->getMessage()]);
        }

        return back()->with('status', 'PDF officiel généré. Imprimez-le pour signature, puis publiez le document.');
    }

    /** Texte type de la lettre de recommandation, proposé dans le formulaire. */
    public function templateText(Request $request)
    {
        Gate::authorize('documents.create');
        $holder = DocumentHolder::find($request->integer('holder'));
        $name = $holder?->full_name ?: trim((string) $request->query('name')) ?: '[Nom du titulaire]';
        $formation = $request->query('formation');
        $certificate = $holder ? OfficialDocument::where('holder_id', $holder->id)->where('status', 'publie')
            ->whereHas('type', fn ($q) => $q->where('is_certificate', true))
            ->when($formation, fn ($q) => $q->orderByRaw('formation = ? desc', [$formation]))->value('reference') : null;

        return response()->json(['text' => \App\Support\FrenchText::recommendation(
            $name, $request->query('civilite', 'M'), $request->query('signataire', 'dg'),
            $formation ? config('evc.formations.'.$formation) : null, $certificate
        )]);
    }

    /** Champs de contenu propres au type de document (vides ignorés). */
    private function details(Request $request): ?array
    {
        $details = array_filter((array) $request->input('details', []), fn ($v) => ! blank($v));

        return $details ?: null;
    }

    private function validated(Request $request, bool $creating): array
    {
        $type = DocumentType::find($request->integer('document_type_id'));
        // Communiqué officiel : émis par l'établissement, sans titulaire.
        $sansTitulaire = $type?->code === 'communique';

        return $request->validate([
            'document_type_id' => ['required', Rule::exists('document_types', 'id')],
            'formation' => [$type?->requires_formation ? 'required' : 'nullable', Rule::in(array_keys(config('evc.formations')))],
            'promotion' => ['nullable', 'string', 'max:40'],
            'holder_id' => [$creating && ! $sansTitulaire ? 'required_without:holder_name' : 'nullable', 'nullable', Rule::exists('document_holders', 'id')],
            'holder_name' => [$creating && ! $sansTitulaire ? 'required_without:holder_id' : 'nullable', 'nullable', 'string', 'max:160'],
            'holder_email' => ['nullable', 'email', 'max:160'],
            'holder_external_id' => ['nullable', 'string', 'max:80', Rule::unique('document_holders', 'external_id')],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'details' => ['nullable', 'array'],
            'details.civilite' => ['nullable', 'in:M,Mme'],
            'details.signataire' => ['nullable', Rule::in(array_keys(config('evc.signataires')))],
            'details.mention' => ['nullable', 'string', 'max:80'],
            'details.statut' => ['nullable', Rule::in(array_keys(config('evc.statuts_formation')))],
            'details.inscription' => ['nullable', 'date', 'before_or_equal:today'],
            'details.fin_formation' => ['nullable', 'date', 'after_or_equal:details.inscription'],
            'details.contenu' => ['nullable', 'string', 'max:160'],
            'details.format' => ['nullable', 'string', 'max:120'],
            'details.poste' => ['nullable', 'string', 'max:160'],
            'details.debut' => ['nullable', 'date'],
            'details.fin' => ['nullable', 'date', 'after_or_equal:details.debut'],
            'details.missions' => ['nullable', 'string', 'max:4000'],
            'details.appreciation' => ['nullable', 'string', 'max:600'],
            'details.destinataire' => ['nullable', 'string', 'max:200'],
            'details.objet' => ['nullable', 'string', 'max:200'],
            'details.destinataires' => ['nullable', 'string', 'max:200'],
            'details.contenu' => ['nullable', 'string', 'max:6000', function ($attribute, $value, $fail) {
                if (mb_strlen(html_entity_decode(strip_tags((string) $value))) > 1600) {
                    $fail('Contenu trop long : le communiqué doit rester concis pour tenir sur une page (environ 1400 caractères visibles).');
                }
            }],
            'details.dispositions' => ['nullable', 'string', 'max:3000'],
            'details.citation' => ['nullable', 'string', 'max:500'],
            'details.corps' => ['nullable', 'string', 'max:6000'],
        ], [
            'formation.required' => 'La formation est obligatoire pour ce type de document.',
            'holder_name.required_without' => 'Choisissez un titulaire existant ou saisissez un nouveau nom.',
            'holder_id.required_without' => 'Choisissez un titulaire existant ou saisissez un nouveau nom.',
            'holder_external_id.unique' => 'Ce matricule My EVC appartient déjà à un autre titulaire.',
            'details.contenu.max' => 'Le contenu du communiqué est trop long.',
        ]);
    }
}
