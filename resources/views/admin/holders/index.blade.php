@extends('layouts.admin')
@section('title', 'Bénéficiaires')
@section('admin')
    <div class="row" style="justify-content:space-between">
        <h1>Bénéficiaires</h1>
        @can('holders.manage')<a class="btn gold" href="{{ route('admin.beneficiaires.create') }}">+ Nouveau titulaire</a>@endcan
    </div>
    <form method="GET" class="search" style="margin-bottom:14px">
        <input name="q" value="{{ request('q') }}" placeholder="Nom, e-mail ou matricule My EVC" style="flex:1">
        <button class="btn" type="submit">Rechercher</button>
    </form>
    <div class="card" style="padding:0;overflow-x:auto">
        <table>
            <thead><tr><th>Nom</th><th>Matricule</th><th>Promotion</th><th>E-mail</th><th>Documents</th>@can('holders.manage')<th></th>@endcan</tr></thead>
            <tbody>
            @forelse ($holders as $h)
                <tr>
                    <td><a href="{{ route('admin.beneficiaires.show', $h) }}"><strong>{{ $h->full_name }}</strong></a></td>
                    <td>{{ $h->external_id ?? '—' }}</td>
                    <td>{{ $h->promotion ?? '—' }}</td>
                    <td>{{ $h->email ?? '—' }}</td>
                    <td>{{ $h->documents_count }}</td>
                    @can('holders.manage')
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('admin.beneficiaires.destroy', $h) }}" style="margin:0" onsubmit="return confirm('Supprimer définitivement {{ $h->full_name }} ?')">
                                @csrf @method('DELETE')
                                <button class="btn danger" type="submit" @disabled($h->documents_count > 0) @if ($h->documents_count > 0) title="Ce titulaire a des documents : suppression impossible" @endif>Supprimer</button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Aucun titulaire.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $holders->links() }}
@endsection
