@extends('layouts.admin')
@section('title', 'Historique')
@section('admin')
    <h1>Historique des modifications</h1>
    <p class="lead">Journal inaltérable : chaque action (création, contrôle, approbation, délivrance, publication, révocation, modification, connexion) est conservée avec son auteur, sa date et son adresse IP.</p>
    <form class="card" method="GET" style="margin-bottom:16px">
        <div class="form" style="grid-template-columns:2fr 1fr 1fr 1fr 1fr">
            <label>Recherche <input name="q" value="{{ request('q') }}" placeholder="Description, référence…"></label>
            <label>Action <select name="action"><option value="">Toutes</option>@foreach ($actions as $a)<option @selected(request('action') === $a)>{{ $a }}</option>@endforeach</select></label>
            <label>Utilisateur <select name="user"><option value="">Tous</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>@endforeach</select></label>
            <label>Du <input type="date" name="from" value="{{ request('from') }}"></label>
            <label>Au <input type="date" name="to" value="{{ request('to') }}"></label>
        </div>
        <div class="acts"><button class="btn" type="submit">Filtrer</button><a class="btn ghost" href="{{ route('admin.audit.index') }}">Réinitialiser</a></div>
    </form>
    <div class="card" style="padding:0;overflow-x:auto">
        <table>
            <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Description</th><th>Dossier</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $l)
                <tr>
                    <td style="white-space:nowrap">{{ $l->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $l->user?->name ?? 'Système' }}</td>
                    <td><span class="tag">{{ $l->action }}</span></td>
                    <td>{{ $l->description }}
                        @if ($l->changes)<details><summary class="fileinfo" style="cursor:pointer">détail</summary><pre style="font-size:12px;white-space:pre-wrap;margin:4px 0">{{ json_encode($l->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif
                    </td>
                    <td>@if ($l->document)<a href="{{ route('admin.documents.show', $l->document_id) }}">{{ $l->document->reference ?? 'n° '.$l->document_id }}</a>@else — @endif</td>
                    <td class="fileinfo">{{ $l->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Aucune entrée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
@endsection
