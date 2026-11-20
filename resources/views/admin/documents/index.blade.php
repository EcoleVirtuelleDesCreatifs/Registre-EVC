@extends('layouts.admin')
@section('title', 'Registre des documents')
@section('admin')
    <div class="row" style="justify-content:space-between">
        <h1>Registre des documents</h1>
        <div class="row">
            @can('documents.create')<a class="btn gold" href="{{ route('admin.documents.create') }}">+ Nouveau document</a>@endcan
            <a class="btn ghost" href="{{ route('admin.documents.export', request()->query()) }}">Exporter (CSV)</a>
        </div>
    </div>

    <form class="card" method="GET" style="margin-bottom:16px">
        <div class="form" style="grid-template-columns:2fr 1fr 1fr 1fr">
            <label>Recherche <input name="q" value="{{ request('q') }}" placeholder="Référence, nom, matricule…"></label>
            <label>Type
                <select name="type"><option value="">Tous</option>
                    @foreach ($types as $t)<option value="{{ $t->code }}" @selected(request('type') === $t->code)>{{ $t->label }}</option>@endforeach
                </select>
            </label>
            <label>Formation
                <select name="formation"><option value="">Toutes</option>
                    @foreach (config('evc.formations') as $code => $label)<option value="{{ $code }}" @selected(request('formation') === $code)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label>Statut
                <select name="status"><option value="">Tous</option>
                    @foreach (config('evc.statuses') as $code => $label)<option value="{{ $code }}" @selected(request('status') === $code)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label>Promotion
                <select name="promotion"><option value="">Toutes</option>
                    @foreach ($promotions as $p)<option @selected(request('promotion') === $p)>{{ $p }}</option>@endforeach
                </select>
            </label>
            <label>Délivré du <input type="date" name="from" value="{{ request('from') }}"></label>
            <label>au <input type="date" name="to" value="{{ request('to') }}"></label>
            <div class="row" style="align-self:end"><button class="btn" type="submit">Filtrer</button><a class="btn ghost" href="{{ route('admin.documents.index') }}">Réinitialiser</a></div>
        </div>
    </form>

    <p class="small" style="margin-top:0">{{ $documents->total() }} document(s)</p>
    <div class="card" style="padding:0;overflow-x:auto">
        <table>
            <thead><tr><th>Référence</th><th>Titulaire</th><th>Document</th><th>Promotion</th><th>Délivré le</th><th>Statut</th><th>PDF</th>@can('documents.edit')<th>Consultation</th>@endcan</tr></thead>
            <tbody>
            @forelse ($documents as $d)
                <tr>
                    <td class="num"><a href="{{ route('admin.documents.show', $d) }}">{{ $d->reference ?? '— (non attribuée)' }}</a>@if ($d->is_legacy) <span class="tag">historique</span>@endif</td>
                    <td>{{ $d->holder?->full_name }}</td>
                    <td>{{ $d->type?->label }}@if ($d->formation)<br><span class="fileinfo">{{ $d->formationLabel() }}</span>@endif</td>
                    <td>{{ $d->promotion ?? '—' }}</td>
                    <td>{{ $d->issued_at ? $d->issued_at->format('d/m/Y') : '—' }}</td>
                    <td>@include('partials.status', ['doc' => $d])</td>
                    <td>@if ($d->pdf_path)<span class="fileinfo">✓</span>@else<span class="fileinfo none">manquant</span>@endif</td>
                    @can('documents.edit')
                        <td>
                            @can('togglePublic', $d)
                                <form method="POST" action="{{ route('admin.documents.consultation', $d) }}" style="margin:0">
                                    @csrf @method('PATCH')
                                    <button class="btn {{ $d->pdf_public ? '' : 'danger' }}" type="submit"
                                        title="{{ $d->pdf_public ? 'Le PDF est visible en ligne : cliquer pour désactiver' : 'Le PDF est masqué : cliquer pour activer la consultation' }}">
                                        {{ $d->pdf_public ? 'En ligne ✓' : 'Désactivé' }}
                                    </button>
                                </form>
                            @else
                                <span class="fileinfo none">—</span>
                            @endcan
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="padding:24px;text-align:center">Aucun document ne correspond à ces critères.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $documents->links() }}
@endsection
