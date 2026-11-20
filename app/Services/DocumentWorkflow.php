<?php

namespace App\Services;

use App\Mail\DocumentPublished;
use App\Mail\WorkflowNotification;
use App\Models\DocumentApproval;
use App\Models\DocumentRevocation;
use App\Models\OfficialDocument;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Processus de délivrance EVC :
 * brouillon → en_verification → (contrôle pédagogique) → approuve → (délivrance : référence) → publie
 *                         ↘ rejete (retour en saisie)                       publie → revoque
 */
class DocumentWorkflow
{
    public function __construct(private ReferenceGenerator $references, private DocumentRenderer $renderer)
    {
    }

    public function submit(OfficialDocument $doc, User $user, ?string $comment = null): void
    {
        $this->expectStatus($doc, ['brouillon', 'rejete'], 'Seul un brouillon ou un dossier rejeté peut être soumis.');

        if ($doc->type->requires_formation && ! $doc->formation) {
            throw new WorkflowException('Indiquez la formation avant de soumettre le dossier.');
        }
        if ($missing = $this->missingDetails($doc)) {
            throw new WorkflowException('Complétez le contenu du document avant de le soumettre : '.implode(', ', $missing).'.');
        }

        DB::transaction(function () use ($doc, $user, $comment) {
            $doc->update([
                'status' => 'en_verification',
                'checklist' => null,
                'pedagogical_validated_at' => null,
                'pedagogical_validated_by' => null,
                'approved_at' => null,
                'approved_by' => null,
                'updated_by' => $user->id,
            ]);
            $this->record($doc, $user, 'soumission', 'effectue', $comment);
            AuditLogger::log('soumission', 'Dossier soumis au contrôle', $doc);
        });

        $this->notifyStep($doc, 'documents.control', 'Dossier à contrôler',
            "{$user->name} a soumis un dossier au contrôle pédagogique.", $user, $comment);
    }

    public function control(OfficialDocument $doc, User $user, array $checklist, bool $approve, ?string $comment): void
    {
        $this->expectStatus($doc, ['en_verification'], 'Le dossier n’est pas en attente de contrôle.');
        if ($doc->pedagogical_validated_at) {
            throw new WorkflowException('Le contrôle pédagogique a déjà été effectué.');
        }

        // Accepte la liste des critères cochés (formulaire) ou un tableau critère => booléen.
        if (array_is_list($checklist)) {
            $checklist = array_fill_keys(array_map('strval', $checklist), true);
        }
        $criteria = array_keys($doc->type->criteria());
        $checked = collect($criteria)->mapWithKeys(fn ($c) => [$c => ! empty($checklist[$c])])->all();

        if ($approve && in_array(false, $checked, true)) {
            throw new WorkflowException('Tous les critères doivent être confirmés pour valider le dossier.');
        }
        if (! $approve && blank($comment)) {
            throw new WorkflowException('Indiquez le motif du rejet.');
        }

        DB::transaction(function () use ($doc, $user, $checked, $approve, $comment) {
            $doc->update($approve
                ? ['checklist' => $checked, 'pedagogical_validated_at' => now(), 'pedagogical_validated_by' => $user->id, 'updated_by' => $user->id]
                : ['checklist' => $checked, 'status' => 'rejete', 'updated_by' => $user->id]);
            $this->record($doc, $user, 'controle_pedagogique', $approve ? 'approuve' : 'rejete', $comment, $checked);
            AuditLogger::log($approve ? 'controle_valide' : 'controle_rejete', $approve ? 'Éligibilité confirmée' : 'Dossier rejeté au contrôle : '.$comment, $doc);
        });

        if ($approve) {
            $this->notifyStep($doc, 'documents.approve', 'Dossier à approuver',
                "Le contrôle pédagogique de {$user->name} est favorable : le dossier attend votre approbation.", $user, $comment);
        } else {
            $this->notifyCreator($doc, 'Dossier rejeté au contrôle',
                "Le contrôle pédagogique de {$user->name} a rejeté le dossier. Corrigez-le puis soumettez-le à nouveau.", $user, $comment);
        }
    }

    public function approve(OfficialDocument $doc, User $user, bool $approve, ?string $comment): void
    {
        $this->expectStatus($doc, ['en_verification'], 'Le dossier n’est pas en attente d’approbation.');
        if (! $doc->pedagogical_validated_at) {
            throw new WorkflowException('Le contrôle pédagogique doit être validé avant l’approbation.');
        }
        if ($doc->pedagogical_validated_by === $user->id) {
            throw new WorkflowException('La personne qui a effectué le contrôle pédagogique ne peut pas aussi approuver le dossier.');
        }
        if (! $approve && blank($comment)) {
            throw new WorkflowException('Indiquez le motif du rejet.');
        }

        DB::transaction(function () use ($doc, $user, $approve, $comment) {
            $doc->update($approve
                ? ['status' => 'approuve', 'approved_at' => now(), 'approved_by' => $user->id, 'updated_by' => $user->id]
                : ['status' => 'rejete', 'updated_by' => $user->id]);
            $this->record($doc, $user, 'approbation', $approve ? 'approuve' : 'rejete', $comment);
            AuditLogger::log($approve ? 'approbation' : 'approbation_rejetee', $approve ? 'Délivrance autorisée' : 'Approbation refusée : '.$comment, $doc);
        });

        if ($approve) {
            $this->notifyStep($doc, 'documents.deliver', 'Document à délivrer',
                "{$user->name} a approuvé le dossier : vous pouvez procéder à la délivrance (attribution de la référence).", $user, $comment);
        } else {
            $this->notifyCreator($doc, 'Approbation refusée',
                "{$user->name} a refusé l'approbation du dossier.", $user, $comment);
        }
    }

    /** Attribue la référence officielle, l'identifiant QR et la date de délivrance. */
    public function deliver(OfficialDocument $doc, User $user, CarbonInterface|string|null $issuedAt = null): string
    {
        $this->expectStatus($doc, ['approuve'], 'Seul un dossier approuvé peut être délivré.');
        if ($doc->reference) {
            throw new WorkflowException('Ce document a déjà une référence : '.$doc->reference);
        }

        $date = $issuedAt ? Carbon::parse($issuedAt) : now();
        if ($date->isAfter(now()->endOfDay())) {
            throw new WorkflowException('La date de délivrance ne peut pas être dans le futur.');
        }

        $reference = DB::transaction(function () use ($doc, $user, $date) {
            $locked = OfficialDocument::whereKey($doc->id)->lockForUpdate()->firstOrFail();
            if ($locked->reference) {
                throw new WorkflowException('Ce document vient de recevoir une référence : '.$locked->reference);
            }

            $reference = $this->references->next($doc->type, $doc->formation, (int) $date->format('Y'));
            $doc->update([
                'reference' => $reference,
                'public_token' => Str::random(40),
                'issued_at' => $date->toDateString(),
                'updated_by' => $user->id,
            ]);
            $this->record($doc, $user, 'delivrance', 'effectue', 'Référence attribuée : '.$reference);
            AuditLogger::log('delivrance', 'Référence attribuée : '.$reference, $doc);

            return $reference;
        });

        // Génération automatique du PDF officiel (référence + QR code imprimés).
        // Un échec n'annule pas la référence : le PDF peut être régénéré depuis la fiche.
        if (! $doc->is_legacy) {
            try {
                $this->renderer->generate($doc->fresh(['type', 'holder']), $user);
            } catch (\Throwable $e) {
                Log::error('Génération du PDF impossible', ['document' => $doc->id, 'error' => $e->getMessage()]);
                AuditLogger::log('generation_echec', 'Génération automatique du PDF impossible : '.Str::limit($e->getMessage(), 150), $doc);
            }
        }

        $this->notifyStep($doc, 'documents.publish', 'Document à publier',
            "{$user->name} a délivré le document (référence {$reference}) : il peut maintenant être publié dans le registre.", $user);

        return $reference;
    }

    public function publish(OfficialDocument $doc, User $user): void
    {
        $this->expectStatus($doc, ['approuve'], 'Seul un document approuvé et délivré peut être publié.');
        if (! $doc->reference) {
            throw new WorkflowException('Délivrez d’abord le document pour lui attribuer sa référence.');
        }
        if (! $doc->pdf_path) {
            throw new WorkflowException('Le PDF officiel est manquant : générez-le depuis la fiche ou joignez la version signée.');
        }

        DB::transaction(function () use ($doc, $user) {
            $doc->update(['status' => 'publie', 'published_at' => now(), 'published_by' => $user->id, 'updated_by' => $user->id]);
            $this->record($doc, $user, 'publication', 'effectue');
            AuditLogger::log('publication', 'Document publié dans le registre public', $doc);
        });

        $this->notifyCreator($doc, 'Document publié',
            "{$user->name} a publié le document : il est vérifiable dans le registre public.", $user);
        $this->notifyHolder($doc);
    }

    public function revoke(OfficialDocument $doc, User $user, string $reason, ?string $decisionRef = null): void
    {
        $this->expectStatus($doc, ['publie'], 'Seul un document publié peut être révoqué.');
        if (blank($reason)) {
            throw new WorkflowException('Le motif de la révocation est obligatoire.');
        }

        DB::transaction(function () use ($doc, $user, $reason, $decisionRef) {
            $doc->update(['status' => 'revoque', 'updated_by' => $user->id]);
            DocumentRevocation::create([
                'document_id' => $doc->id,
                'reason' => $reason,
                'decision_reference' => $decisionRef,
                'revoked_by' => $user->id,
                'revoked_at' => now(),
            ]);
            AuditLogger::log('revocation', 'Révocation : '.$reason, $doc, null, ['decision' => $decisionRef]);
        });
    }

    /** Envoie un mail aux utilisateurs actifs habilités pour l'étape suivante. */
    private function notifyStep(OfficialDocument $doc, string $permission, string $title, string $message, User $actor, ?string $comment = null): void
    {
        $roles = config("evc.permissions.{$permission}", []);
        $recipients = User::where('is_active', true)->whereIn('role', $roles)->whereKeyNot($actor->id)->get();

        $this->sendMails($doc, $recipients, $title, $message, $comment);
    }

    /** Informe le créateur du dossier (rejet, publication). */
    private function notifyCreator(OfficialDocument $doc, string $title, string $message, User $actor, ?string $comment = null): void
    {
        $creator = $doc->creator;
        $recipients = ($creator && $creator->is_active && $creator->id !== $actor->id) ? collect([$creator]) : collect();

        $this->sendMails($doc, $recipients, $title, $message, $comment);
    }

    /** Félicite le titulaire par mail lorsque son document devient vérifiable publiquement. */
    private function notifyHolder(OfficialDocument $doc): void
    {
        $email = $doc->holder?->email;
        if (! $email) {
            return;
        }
        try {
            Mail::to($email)->send(new DocumentPublished($doc->fresh(['type', 'holder'])));
        } catch (\Throwable $e) {
            Log::warning('Notification titulaire non envoyée', ['document' => $doc->id, 'to' => $email, 'error' => $e->getMessage()]);
        }
    }

    private function sendMails(OfficialDocument $doc, $recipients, string $title, string $message, ?string $comment): void
    {
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->send(new WorkflowNotification($doc->fresh(['type', 'holder']), $title, $message, $comment));
            } catch (\Throwable $e) {
                Log::warning('Notification workflow non envoyée', ['document' => $doc->id, 'to' => $recipient->email, 'error' => $e->getMessage()]);
            }
        }
    }

    private function expectStatus(OfficialDocument $doc, array $allowed, string $message): void
    {
        if (! in_array($doc->status, $allowed, true)) {
            throw new WorkflowException($message.' (statut actuel : '.$doc->statusLabel().')');
        }
    }

    private function record(OfficialDocument $doc, User $user, string $step, string $decision, ?string $comment = null, ?array $checklist = null): void
    {
        DocumentApproval::create([
            'document_id' => $doc->id,
            'step' => $step,
            'decision' => $decision,
            'comment' => $comment,
            'checklist' => $checklist,
            'user_id' => $user->id,
        ]);
    }

    /** Champs de contenu indispensables à la génération du document. */
    public function missingDetails(OfficialDocument $doc): array
    {
        $missing = [];
        $code = $doc->type->code;
        if ($code === 'attestation_formation') {
            if (! $doc->detail('inscription')) {
                $missing[] = 'date d’inscription';
            }
            if ($doc->detail('statut') === 'achevee' && ! $doc->detail('fin_formation')) {
                $missing[] = 'date de fin de formation';
            }
        }
        if ($code === 'attestation_stage') {
            if (! $doc->detail('poste')) {
                $missing[] = 'poste';
            }
            if (! $doc->detail('debut') || ! $doc->detail('fin')) {
                $missing[] = 'période du stage';
            }
        }

        return $missing;
    }
}
