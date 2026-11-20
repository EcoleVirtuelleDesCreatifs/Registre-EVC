@extends('layouts.admin')
@section('title', 'Import Excel / CSV')
@section('admin')
    <h1>Import Excel / CSV</h1>
    <p class="lead">Chaque ligne est contrôlée avant enregistrement : champs obligatoires, dates, doublons, titulaires existants. Rien n’est enregistré avant votre confirmation.</p>
    <div class="cols">
        <form class="card form" method="POST" action="{{ route('admin.imports.analyse') }}" enctype="multipart/form-data" style="grid-template-columns:1fr">
            @csrf
            <label>Fichier CSV <input type="file" name="fichier" accept=".csv,text/csv" required></label>
            @error('fichier')<div class="err-text">{{ $message }}</div>@enderror
            <span class="fileinfo">Depuis Excel : Fichier › Enregistrer sous › « CSV (séparateur : point-virgule) ». UTF-8 ou Windows-1252 acceptés.</span>
            <fieldset style="border:1px solid var(--line);border-radius:10px;padding:12px">
                <legend class="small">Mode d’import</legend>
                <label class="check"><input type="radio" name="mode" value="brouillons" checked> <span><strong>Nouveaux dossiers</strong> — créés en brouillon, puis soumis au circuit de validation. Les références seront attribuées à la délivrance.</span></label>
                @can('imports.history')
                    <label class="check" style="margin-top:8px"><input type="radio" name="mode" value="historique"> <span><strong>Reprise de documents déjà délivrés</strong> — publiés directement avec leur référence imprimée d’origine (Direction générale uniquement).</span></label>
                @endcan
            </fieldset>
            <button class="btn" type="submit">Analyser le fichier</button>
        </form>
        <div class="card">
            <h2>Colonnes attendues</h2>
            <table>
                <tr><td class="num">type</td><td>certificat, attestation_formation, attestation_stage, recommandation</td></tr>
                <tr><td class="num">formation</td><td>DG, CM ou GIA (obligatoire pour certificats et attestations de formation)</td></tr>
                <tr><td class="num">titulaire</td><td>Nom complet — obligatoire</td></tr>
                <tr><td class="num">promotion</td><td>facultatif</td></tr>
                <tr><td class="num">email</td><td>facultatif</td></tr>
                <tr><td class="num">matricule</td><td>matricule My EVC, facultatif</td></tr>
                <tr><td class="num">date_delivrance</td><td>JJ/MM/AAAA — obligatoire en reprise</td></tr>
                <tr><td class="num">reference</td><td>reprise uniquement : référence imprimée</td></tr>
                <tr><td class="num">note</td><td>note interne, facultatif</td></tr>
                <tr><td class="num">civilite</td><td>M ou Mme : accords sur le document généré (facultatif, M par défaut)</td></tr>
            </table>
            <div class="acts"><a class="btn ghost" href="{{ route('admin.imports.template') }}">Télécharger le modèle</a></div>
        </div>
    </div>
@endsection
