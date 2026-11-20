<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentHolder;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use App\Services\AuditLogger;
use App\Support\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Intégration My EVC.
 * My EVC transmet les dossiers d'apprenants éligibles ; la délivrance reste une décision humaine
 * (contrôle pédagogique + approbation dans le registre).
 */
class MyEvcController extends Controller
{
    public function show(string $reference)
    {
        $doc = OfficialDocument::with(['type', 'holder'])->where('reference', Reference::normalize($reference))->first();

        return $doc ? response()->json($this->present($doc)) : response()->json(['message' => 'Référence inconnue.'], 404);
    }

    public function holderDocuments(string $externalId)
    {
        $holder = DocumentHolder::where('external_id', $externalId)->first();
        if (! $holder) {
            return response()->json(['message' => 'Apprenant inconnu du registre.'], 404);
        }

        return response()->json([
            'apprenant' => ['matricule' => $holder->external_id, 'nom' => $holder->full_name],
            'documents' => $holder->documents()->with('type')->get()->map(fn ($d) => $this->present($d)),
        ]);
    }

    /** Crée un dossier en brouillon à partir des données My EVC (aucune référence attribuée). */
    public function storeDossier(Request $request)
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'max:80'],
            'nom' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email'],
            'promotion' => ['nullable', 'string', 'max:40'],
            'type' => ['required', Rule::exists('document_types', 'code')],
            'formation' => ['nullable', Rule::in(array_keys(config('evc.formations')))],
            'criteres' => ['nullable', 'array'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
            'civilite' => ['nullable', 'in:M,Mme'],
            'details' => ['nullable', 'array'],
        ]);
        $type = DocumentType::where('code', $data['type'])->firstOrFail();
        if ($type->requires_formation && empty($data['formation'])) {
            return response()->json(['message' => 'Formation obligatoire pour ce type.'], 422);
        }

        $doc = DB::transaction(function () use ($data, $type) {
            $holder = DocumentHolder::firstOrCreate(['external_id' => $data['matricule']], [
                'full_name' => DocumentHolder::normalizeName($data['nom']),
                'email' => $data['email'] ?? null,
                'promotion' => $data['promotion'] ?? null,
            ]);

            $existing = OfficialDocument::where('holder_id', $holder->id)->where('document_type_id', $type->id)
                ->where('formation', $data['formation'] ?? null)->whereNotIn('status', ['rejete', 'revoque'])->first();
            if ($existing) {
                return $existing;
            }

            $doc = OfficialDocument::create([
                'document_type_id' => $type->id,
                'holder_id' => $holder->id,
                'formation' => $data['formation'] ?? null,
                'promotion' => $data['promotion'] ?? null,
                'status' => 'brouillon',
                'source' => 'my_evc',
                'internal_note' => trim(($data['commentaire'] ?? '')."\nCritères transmis par My EVC : ".json_encode($data['criteres'] ?? [], JSON_UNESCAPED_UNICODE)),
                'details' => array_filter(array_merge(
                    \Illuminate\Support\Arr::only($data['details'] ?? [], ['inscription', 'statut', 'contenu', 'format', 'fin_formation', 'poste', 'debut', 'fin', 'missions', 'mention']),
                    ['civilite' => $data['civilite'] ?? null]
                )) ?: null,
            ]);
            AuditLogger::log('creation', 'Dossier transmis par My EVC', $doc);

            return $doc;
        });

        return response()->json($this->present($doc), $doc->wasRecentlyCreated ? 201 : 200);
    }

    private function present(OfficialDocument $d): array
    {
        return [
            'reference' => $d->reference,
            'type' => $d->type->code,
            'formation' => $d->formation,
            'titulaire' => $d->holder->full_name,
            'matricule' => $d->holder->external_id,
            'statut' => $d->status,
            'delivre_le' => $d->issued_at?->toDateString(),
            'url_verification' => $d->isPublic() ? route('public.show', $d->reference) : null,
        ];
    }
}
