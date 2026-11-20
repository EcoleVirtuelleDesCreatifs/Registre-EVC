@extends('layouts.admin')
@section('title', 'Contrôle de l’import')
@section('admin')
    @php
        $ok = collect($rows)->filter(fn ($r) => ! $r['errors'])->count();
        $ko = count($rows) - $ok;
    @endphp
    <h1>Contrôle de l’import</h1>
    <p class="lead">Mode : <strong>{{ $mode === 'historique' ? 'reprise de documents déjà délivrés' : 'nouveaux dossiers en brouillon' }}</strong>.
        {{ count($rows) }} ligne(s) lue(s) : <strong style="color:var(--ok)">{{ $ok }} valide(s)</strong>, <strong style="color:var(--bad)">{{ $ko }} en erreur</strong>.</p>

    <div class="card" style="padding:0;overflow-x:auto">
        <table>
            <thead><tr><th>Ligne</th><th>Type</th><th>Formation</th><th>Titulaire</th><th>Date</th>@if ($mode === 'historique')<th>Référence</th>@endif<th>Contrôle</th></tr></thead>
            <tbody>
            @foreach ($rows as $r)
                <tr class="{{ $r['errors'] ? 'line-err' : 'line-ok' }}">
                    <td>{{ $r['line'] }}</td>
                    <td>{{ $r['data']['type'] ?? '—' }}</td>
                    <td>{{ $r['data']['formation'] ?? '—' }}</td>
                    <td>{{ $r['data']['titulaire'] }}@if ($r['data']['holder_id'])<br><span class="fileinfo">titulaire existant</span>@endif</td>
                    <td>{{ $r['data']['date'] ? \Illuminate\Support\Carbon::parse($r['data']['date'])->format('d/m/Y') : '—' }}</td>
                    @if ($mode === 'historique')<td class="num">{{ $r['data']['reference'] ?? '—' }}</td>@endif
                    <td>
                        @foreach ($r['errors'] as $e)<div class="err-text">✗ {{ $e }}</div>@endforeach
                        @foreach ($r['warnings'] as $w)<div class="warn-text">⚠ {{ $w }}</div>@endforeach
                        @if (! $r['errors'] && ! $r['warnings'])<span style="color:var(--ok)">✓</span>@endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="acts" style="margin-top:16px">
        @if ($ok)
            <form method="POST" action="{{ route('admin.imports.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button class="btn gold" type="submit">Importer les {{ $ok }} ligne(s) valide(s)</button>
            </form>
        @endif
        <a class="btn ghost" href="{{ route('admin.imports.create') }}">Corriger le fichier et recommencer</a>
    </div>
    @if ($ko)<p class="small">Les lignes en erreur sont ignorées. Corrigez-les dans le fichier puis importez-les à nouveau.</p>@endif
@endsection
