@extends('layouts.admin')
@section('title', 'Sauvegardes')
@section('admin')
    <div class="row" style="justify-content:space-between">
        <h1>Sauvegardes</h1>
        <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button class="btn gold" type="submit">Créer une sauvegarde maintenant</button></form>
    </div>
    @error('backup')<div class="alert err">{{ $message }}</div>@enderror
    <p class="lead">Une sauvegarde complète (base de données + PDF et aperçus) est créée automatiquement chaque nuit à 02 h 30. Les {{ config('evc.backups_keep') }} plus récentes sont conservées. Téléchargez-en régulièrement une copie hors du serveur.</p>
    <div class="card" style="padding:0">
        <table>
            <thead><tr><th>Fichier</th><th>Date</th><th>Taille</th><th></th></tr></thead>
            <tbody>
            @forelse ($backups as $b)
                <tr>
                    <td class="num">{{ $b['name'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($b['date'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    <td>{{ number_format($b['size'] / 1048576, 1, ',', ' ') }} Mo</td>
                    <td><a class="btn ghost" href="{{ route('admin.backups.download', $b['name']) }}">Télécharger</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="padding:24px;text-align:center">Aucune sauvegarde pour l’instant.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="notice" style="margin-top:16px"><strong>Restauration</strong> — pour des raisons de sécurité, elle se fait uniquement depuis le serveur :
        <code>php artisan evc:restore storage/app/private/backups/NOM.zip</code>. Une sauvegarde de sécurité de l’état actuel est créée avant toute restauration.</div>
@endsection
