@extends('layouts.admin')
@section('title', 'Validations')
@push('head')<style>#del-dialog::backdrop{background:rgba(28,37,87,.45);backdrop-filter:blur(2px)}</style>@endpush
@section('admin')
    <h1>Circuit de validation</h1>
    <p class="lead">Création → contrôle pédagogique → approbation → délivrance → publication. Chaque étape est tracée.</p>
    @php
        $queues = [
            ['À contrôler — Direction pédagogique', $toControl, $user->hasPermission('documents.control')],
            ['À approuver — Direction générale', $toApprove, $user->hasPermission('documents.approve')],
            ['Approuvés, référence à attribuer', $toDeliver, $user->hasPermission('documents.deliver')],
            ['Délivrés, à publier (PDF signé requis)', $toPublish, $user->hasPermission('documents.publish') || $user->hasPermission('documents.attach')],
            ['Rejetés, à corriger', $rejected, $user->hasPermission('documents.edit')],
        ];
    @endphp
    @foreach ($queues as [$title, $items, $mine])
        <div class="card" style="margin-bottom:16px;{{ $mine && $items->count() ? 'border-color:var(--gold)' : '' }}">
            <div class="row" style="justify-content:space-between">
                <h2 style="margin:0">{{ $title }}</h2>
                <span class="pill {{ $items->count() ? 'en_verification' : 'brouillon' }}">{{ $items->count() }}</span>
            </div>
            @if ($items->count())
                <table style="margin-top:10px">
                    <thead><tr><th>Dossier</th><th>Titulaire</th><th>Document</th><th>Depuis le</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($items as $d)
                        <tr>
                            <td class="num">{{ $d->reference ?? 'n° '.$d->id }}</td>
                            <td>{{ $d->holder?->full_name }}</td>
                            <td>{{ $d->type?->label }}@if ($d->formation) — {{ $d->formationLabel() }}@endif</td>
                            <td>{{ $d->updated_at?->format('d/m/Y') }}</td>
                            <td style="text-align:right;white-space:nowrap">
                                <a class="btn ghost" href="{{ route('admin.documents.show', $d) }}">{{ $mine ? 'Traiter' : 'Voir' }}</a>
                                @can('delete', $d)
                                    <button type="button" class="btn danger" data-del-url="{{ route('admin.documents.destroy', $d) }}"
                                        data-del-name="{{ $d->holder?->full_name ?? 'Dossier n° '.$d->id }}"
                                        data-del-doc="{{ $d->type?->label }}{{ $d->formation ? ' — '.$d->formationLabel() : '' }}"
                                        data-del-required="{{ in_array($d->status, ['en_verification', 'approuve'], true) ? '1' : '0' }}">🗑 Supprimer</button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted" style="margin:8px 0 0">Rien en attente.</p>
            @endif
        </div>
    @endforeach

    <dialog id="del-dialog" style="border:1px solid var(--line);border-radius:14px;padding:0;max-width:460px;width:calc(100% - 32px);box-shadow:0 20px 50px rgba(28,37,87,.25)">
        <form method="POST" id="del-form" style="padding:22px">
            @csrf @method('DELETE')
            <h2 style="margin:0 0 6px;color:var(--bad);font-family:'Libre Baskerville',serif;font-weight:400;font-size:19px">Supprimer ce dossier ?</h2>
            <p style="margin:0 0 4px"><strong id="del-name"></strong></p>
            <p class="small" style="margin:0 0 14px" id="del-doc"></p>
            <label class="small" style="display:block">Motif de la suppression <span id="del-req">(obligatoire)</span>
                <textarea class="f" name="motif" id="del-motif" rows="3" maxlength="500" style="width:100%;margin-top:4px" placeholder="Ex. : doublon, inscription annulée, erreur de saisie…"></textarea>
            </label>
            <p class="small" style="margin:10px 0 16px">Aucune référence n’a été attribuée : le dossier sort du circuit. L’opération et son motif sont inscrits dans l’historique.</p>
            <div class="row" style="justify-content:flex-end">
                <button type="button" class="btn ghost" id="del-cancel">Annuler</button>
                <button type="submit" class="btn danger" style="background:var(--bad);color:#fff;border-color:var(--bad)">Supprimer définitivement</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(function () {
    const dlg = document.getElementById('del-dialog');
    if (!dlg) return;
    const form = document.getElementById('del-form'), motif = document.getElementById('del-motif');
    document.querySelectorAll('[data-del-url]').forEach((b) => b.addEventListener('click', () => {
        form.action = b.dataset.delUrl;
        document.getElementById('del-name').textContent = b.dataset.delName;
        document.getElementById('del-doc').textContent = b.dataset.delDoc;
        const req = b.dataset.delRequired === '1';
        motif.required = req; motif.value = '';
        document.getElementById('del-req').textContent = req ? '(obligatoire)' : '(facultatif)';
        if (typeof dlg.showModal === 'function') { dlg.showModal(); motif.focus(); }
        else if (window.confirm('Supprimer le dossier de ' + b.dataset.delName + ' ?')) { form.submit(); }
    }));
    document.getElementById('del-cancel').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
})();
</script>
@endpush
