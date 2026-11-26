<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentHolder;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rubrique « Étudiants par document » : pour chaque type de document
 * (certificat, attestations, lettre de recommandation), la liste des
 * étudiants concernés, avec regroupement, tri et recherche instantanée.
 */
class StudentsByDocumentController extends Controller
{
    /** Au-delà, la liste est tronquée (la recherche et les filtres restent disponibles). */
    private const LIMIT = 3000;

    /** Statuts considérés « en cours » (avant délivrance). */
    private const IN_PROGRESS = ['brouillon', 'en_verification', 'approuve', 'rejete'];

    public function index(Request $request)
    {
        Gate::authorize('viewAny', OfficialDocument::class);

        $types = DocumentType::ordered();
        $filters = $this->filters($request);

        // Compteurs par type (avec les filtres formation / promotion / statut)
        $counts = $this->base($filters)
            ->selectRaw('document_type_id, count(*) as docs, count(distinct holder_id) as students')
            ->groupBy('document_type_id')
            ->get()
            ->keyBy('document_type_id');

        // Type sélectionné : celui demandé, sinon le premier qui contient des documents
        $type = $types->firstWhere('code', $request->input('type'))
            ?? $types->first(fn ($t) => ($counts[$t->id]->docs ?? 0) > 0)
            ?? $types->first();

        $documents = $type
            ? $this->base($filters)->where('document_type_id', $type->id)
                ->with('holder')
                ->orderBy(DocumentHolder::select('full_name')->whereColumn('document_holders.id', 'official_documents.holder_id'))
                ->orderByDesc('issued_at')
                ->limit(self::LIMIT + 1)
                ->get()
            : collect();

        $truncated = $documents->count() > self::LIMIT;
        $documents = $documents->take(self::LIMIT);

        $rows = $documents->map(fn (OfficialDocument $d) => [
            'id' => $d->id,
            'holder_id' => $d->holder_id,
            'name' => $d->holder?->full_name ?? '—',
            'matricule' => $d->holder?->external_id,
            'email' => $d->holder?->email,
            'promotion' => $d->promotion ?: $d->holder?->promotion,
            'formation' => $d->formation,
            'formation_label' => $d->formationLabel(),
            'reference' => $d->reference,
            'legacy' => (bool) $d->is_legacy,
            'issued' => $d->issued_at?->format('Y-m-d'),
            'issued_fr' => $d->issued_at?->format('d/m/Y'),
            'status' => $d->status,
            'status_label' => $d->statusLabel(),
            'pdf' => (bool) $d->pdf_path,
            'url' => route('admin.documents.show', $d),
            'holder_url' => $d->holder ? route('admin.beneficiaires.show', $d->holder) : null,
        ])->values();

        // Répartition par formation pour le type sélectionné
        $byFormation = $type
            ? $documents->groupBy(fn ($d) => $d->formation ?: '')->map->count()
            : collect();

        $summary = [
            'students' => $documents->pluck('holder_id')->unique()->count(),
            'documents' => $documents->count(),
            'publies' => $documents->where('status', 'publie')->count(),
            'en_cours' => $documents->whereIn('status', self::IN_PROGRESS)->count(),
            'revoques' => $documents->where('status', 'revoque')->count(),
        ];

        return view('admin.students.index', [
            'types' => $types,
            'type' => $type,
            'counts' => $counts,
            'rows' => $rows,
            'summary' => $summary,
            'byFormation' => $byFormation,
            'truncated' => $truncated,
            'limit' => self::LIMIT,
            'promotions' => OfficialDocument::whereNotNull('promotion')->distinct()->orderBy('promotion')->pluck('promotion'),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', OfficialDocument::class);

        $filters = $this->filters($request);
        $type = DocumentType::ordered()->firstWhere('code', $request->input('type'));
        abort_unless($type, 404);

        $query = $this->base($filters)->where('document_type_id', $type->id)->with('holder')->orderBy('id');
        AuditLogger::log('export', 'Export CSV des étudiants — '.$type->label);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['etudiant', 'matricule', 'email', 'promotion', 'formation', 'reference', 'date_delivrance', 'statut', 'pdf_joint'], ';');
            $query->chunk(500, function ($docs) use ($out) {
                foreach ($docs as $d) {
                    fputcsv($out, [
                        $d->holder?->full_name, $d->holder?->external_id, $d->holder?->email,
                        $d->promotion ?: $d->holder?->promotion, $d->formationLabel(), $d->reference,
                        $d->issued_at?->format('d/m/Y'), $d->statusLabel(), $d->pdf_path ? 'oui' : 'non',
                    ], ';');
                }
            });
            fclose($out);
        }, 'etudiants-'.$type->code.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $status = $request->input('status');

        return [
            'formation' => isset(config('evc.formations')[(string) $request->input('formation')]) ? (string) $request->input('formation') : null,
            'promotion' => $request->filled('promotion') ? (string) $request->input('promotion') : null,
            // « delivres » = publiés + révoqués (documents ayant une référence officielle)
            'status' => in_array($status, ['delivres', 'en_cours', ...array_keys(config('evc.statuses'))], true) ? $status : null,
        ];
    }

    private function base(array $f): Builder
    {
        return OfficialDocument::query()
            ->when($f['formation'], fn ($q, $v) => $q->where('formation', $v))
            ->when($f['promotion'], fn ($q, $v) => $q->where('promotion', $v))
            ->when($f['status'], fn ($q, $v) => match ($v) {
                'delivres' => $q->whereIn('status', ['publie', 'revoque']),
                'en_cours' => $q->whereIn('status', self::IN_PROGRESS),
                default => $q->where('status', $v),
            });
    }
}
