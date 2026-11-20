@extends('layouts.admin')
@section('title', $holder->full_name)
@section('admin')
    <div class="row" style="justify-content:space-between">
        <h1>{{ $holder->full_name }}</h1>
        <div class="row">
            @can('holders.manage')
                <a class="btn ghost" href="{{ route('admin.beneficiaires.edit', $holder) }}">Modifier</a>
                <form method="POST" action="{{ route('admin.beneficiaires.destroy', $holder) }}" style="margin:0" onsubmit="return confirm('Supprimer définitivement {{ $holder->full_name }} ?')">
                    @csrf @method('DELETE')
                    <button class="btn danger" type="submit" @disabled($holder->documents->isNotEmpty()) @if ($holder->documents->isNotEmpty()) title="Ce titulaire a des documents : suppression impossible" @endif>Supprimer</button>
                </form>
            @endcan
            @can('documents.create')<a class="btn gold" href="{{ route('admin.documents.create', ['beneficiaire' => $holder->id]) }}">+ Document pour ce titulaire</a>@endcan
        </div>
    </div>
    <div class="cols">
        <div class="card">
            <h2>Documents</h2>
            @forelse ($holder->documents as $d)
                <div class="row" style="justify-content:space-between;border-bottom:1px solid #f0e9da;padding:10px 0">
                    <div><a href="{{ route('admin.documents.show', $d) }}"><strong>{{ $d->reference ?? 'Dossier n° '.$d->id }}</strong></a><br>
                        <span class="fileinfo">{{ $d->type?->label }}@if ($d->formation) — {{ $d->formationLabel() }}@endif · {{ $d->issuedFr() }}</span></div>
                    @include('partials.status', ['doc' => $d])
                </div>
            @empty <p class="muted">Aucun document.</p> @endforelse
        </div>
        <div class="card">
            <h2>Fiche</h2>
            <div class="kv">
                <span class="k">Matricule My EVC</span><span class="v">{{ $holder->external_id ?? '—' }}</span>
                <span class="k">Promotion</span><span class="v">{{ $holder->promotion ?? '—' }}</span>
                <span class="k">E-mail</span><span class="v">{{ $holder->email ?? '—' }}</span>
                <span class="k">Téléphone</span><span class="v">{{ $holder->phone ?? '—' }}</span>
                <span class="k">Naissance</span><span class="v">{{ $holder->birth_date?->format('d/m/Y') ?? '—' }}</span>
                @if ($holder->notes)<span class="k">Notes</span><span class="v">{{ $holder->notes }}</span>@endif
            </div>
        </div>
    </div>
@endsection
