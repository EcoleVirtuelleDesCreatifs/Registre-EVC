<?php

namespace App\Policies;

use App\Models\OfficialDocument;
use App\Models\User;

/** Qui peut faire quoi sur un dossier, selon son rôle ET l'étape du dossier. */
class OfficialDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function view(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.create');
    }

    public function update(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.edit') && $doc->isEditable();
    }

    /** Activer/désactiver la consultation en ligne du PDF : possible dès qu'un PDF existe. */
    public function togglePublic(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.edit') && $doc->pdf_path !== null;
    }

    public function submit(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.submit') && $doc->isEditable();
    }

    public function control(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.control')
            && $doc->status === 'en_verification' && ! $doc->pedagogical_validated_at;
    }

    public function approve(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.approve')
            && $doc->status === 'en_verification' && $doc->pedagogical_validated_at
            && $doc->pedagogical_validated_by !== $user->id;
    }

    public function deliver(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.deliver') && $doc->status === 'approuve' && ! $doc->reference;
    }

    /** (Re)générer le PDF officiel : document délivré, pas encore publié, non historique. */
    public function generate(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.deliver') && $doc->status === 'approuve' && $doc->reference && ! $doc->is_legacy;
    }

    public function attach(User $user, OfficialDocument $doc): bool
    {
        // Le PDF signé se joint après la délivrance (la référence doit figurer dessus).
        // Remplacer le PDF d'un document déjà publié est réservé à la Direction générale.
        if ($doc->status === 'publie') {
            return $user->hasPermission('documents.publish');
        }

        return $user->hasPermission('documents.attach')
            && in_array($doc->status, ['brouillon', 'rejete', 'en_verification', 'approuve'], true);
    }

    public function publish(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.publish') && $doc->status === 'approuve' && $doc->reference;
    }

    public function revoke(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.revoke') && $doc->status === 'publie';
    }

    /** Suppression : uniquement un brouillon jamais soumis (protection contre la suppression définitive). */
    public function delete(User $user, OfficialDocument $doc): bool
    {
        return $user->hasPermission('documents.edit') && $doc->status === 'brouillon' && ! $doc->reference;
    }
}
