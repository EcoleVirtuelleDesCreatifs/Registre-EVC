<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficialDocument;
use App\Services\DocumentWorkflow;
use App\Services\WorkflowException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ValidationController extends Controller
{
    public function __construct(private DocumentWorkflow $workflow)
    {
    }

    /** File d'attente adaptée au rôle de l'utilisateur. */
    public function index(Request $request)
    {
        $user = $request->user();
        $base = fn () => OfficialDocument::with(['type', 'holder'])->orderBy('updated_at');

        return view('admin.validations.index', [
            'toControl' => $base()->where('status', 'en_verification')->whereNull('pedagogical_validated_at')->get(),
            'toApprove' => $base()->where('status', 'en_verification')->whereNotNull('pedagogical_validated_at')->get(),
            'toDeliver' => $base()->where('status', 'approuve')->whereNull('reference')->get(),
            'toPublish' => $base()->where('status', 'approuve')->whereNotNull('reference')->get(),
            'rejected' => $base()->where('status', 'rejete')->get(),
            'user' => $user,
        ]);
    }

    public function submit(Request $request, OfficialDocument $document)
    {
        Gate::authorize('submit', $document);

        return $this->run(fn () => $this->workflow->submit($document, $request->user(), $request->input('comment')),
            $document, 'Dossier soumis au contrôle pédagogique.');
    }

    public function control(Request $request, OfficialDocument $document)
    {
        Gate::authorize('control', $document);
        $request->validate(['decision' => ['required', 'in:approuve,rejete'], 'comment' => ['nullable', 'string', 'max:2000']]);

        return $this->run(fn () => $this->workflow->control(
            $document, $request->user(), (array) $request->input('criteres', []),
            $request->input('decision') === 'approuve', $request->input('comment')
        ), $document, $request->input('decision') === 'approuve' ? 'Éligibilité confirmée. Le dossier attend l’approbation.' : 'Dossier rejeté.');
    }

    public function approve(Request $request, OfficialDocument $document)
    {
        Gate::authorize('approve', $document);
        $request->validate(['decision' => ['required', 'in:approuve,rejete'], 'comment' => ['nullable', 'string', 'max:2000']]);

        return $this->run(fn () => $this->workflow->approve(
            $document, $request->user(), $request->input('decision') === 'approuve', $request->input('comment')
        ), $document, $request->input('decision') === 'approuve' ? 'Délivrance autorisée.' : 'Approbation refusée.');
    }

    public function deliver(Request $request, OfficialDocument $document)
    {
        Gate::authorize('deliver', $document);
        $request->validate(['issued_at' => ['required', 'date', 'before_or_equal:today', 'after:2019-12-31']]);

        $reference = null;
        $response = $this->run(function () use ($document, $request, &$reference) {
            $reference = $this->workflow->deliver($document, $request->user(), $request->input('issued_at'));
        }, $document, 'Document délivré.');

        if (! $reference) {
            return $response;
        }
        if ($document->fresh()->is_legacy || $document->fresh()->generated_pdf_path) {
            return $response->with('status', "Référence attribuée : {$reference}. Le PDF officiel a été généré avec la référence et le QR code : imprimez-le pour signature, puis publiez le document.");
        }

        return $response->with('status', "Référence attribuée : {$reference}.")
            ->withErrors(['workflow' => 'Le PDF officiel n’a pas pu être généré automatiquement. Utilisez « Générer le PDF » dans le panneau Document officiel (le détail figure dans l’historique).']);
    }

    public function publish(Request $request, OfficialDocument $document)
    {
        Gate::authorize('publish', $document);

        return $this->run(fn () => $this->workflow->publish($document, $request->user()),
            $document, 'Document publié : il est maintenant vérifiable dans le registre public.');
    }

    public function revoke(Request $request, OfficialDocument $document)
    {
        Gate::authorize('revoke', $document);
        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'decision_reference' => ['nullable', 'string', 'max:120'],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Confirmez la révocation.', 'reason.min' => 'Précisez le motif (10 caractères minimum).']);

        return $this->run(fn () => $this->workflow->revoke($document, $request->user(), $request->input('reason'), $request->input('decision_reference')),
            $document, 'Document révoqué. Sa référence reste réservée et la vérification publique l’indique comme révoqué.');
    }

    private function run(callable $action, OfficialDocument $document, string $success)
    {
        try {
            $action();
        } catch (WorkflowException $e) {
            return redirect()->route('admin.documents.show', $document)->withErrors(['workflow' => $e->getMessage()]);
        }

        return redirect()->route('admin.documents.show', $document)->with('status', $success);
    }
}
