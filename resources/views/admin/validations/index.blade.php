@extends('layouts.admin')
@section('title', 'Validations')
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
                            <td><a class="btn ghost" href="{{ route('admin.documents.show', $d) }}">{{ $mine ? 'Traiter' : 'Voir' }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="muted" style="margin:8px 0 0">Rien en attente.</p>
            @endif
        </div>
    @endforeach
@endsection
