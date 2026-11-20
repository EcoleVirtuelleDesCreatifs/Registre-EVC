@extends('layouts.app')

@section('title', 'Confidentialité du registre')

@section('content')
    <h1>Politique de confidentialité du registre</h1>
    <div class="card" style="line-height:1.7">
        <h2 class="serif" style="font-weight:400;color:var(--navy);font-size:20px;margin-top:0">Finalité</h2>
        <p>Le registre officiel permet aux employeurs, organismes et partenaires de vérifier l’authenticité des documents délivrés par l’École Virtuelle des Créatifs (EVC), à la demande de leurs titulaires.</p>

        <h2 class="serif" style="font-weight:400;color:var(--navy);font-size:20px">Informations accessibles au public</h2>
        <p>Uniquement à partir de la référence exacte ou du QR code d’un document : nom du titulaire, type de document, formation, date de délivrance, statut (valide ou révoqué) et, lorsque la direction l’autorise, l’exemplaire officiel du document. Aucune liste de titulaires n’est consultable et aucune recherche par nom n’est possible.</p>

        <h2 class="serif" style="font-weight:400;color:var(--navy);font-size:20px">Informations jamais publiées</h2>
        <p>Coordonnées, date de naissance, matricule, notes internes, dossiers en cours de validation ou rejetés, historique des décisions.</p>

        <h2 class="serif" style="font-weight:400;color:var(--navy);font-size:20px">Journal des vérifications</h2>
        <p>Chaque consultation est enregistrée (date, référence saisie, résultat) avec une empreinte chiffrée et irréversible de l’adresse IP, afin de détecter les tentatives de fraude et les recherches automatisées. Le nombre de consultations est limité.</p>

        <h2 class="serif" style="font-weight:400;color:var(--navy);font-size:20px">Conservation</h2>
        <p>Les documents délivrés restent inscrits au registre pour permettre leur vérification dans la durée. Un document annulé reste visible comme « révoqué » et sa référence n’est jamais réattribuée.</p>

        <h2 class="serif" style="font-weight:400;color:var(--navy);font-size:20px">Vos droits</h2>
        <p>Tout titulaire peut demander l’accès, la rectification d’une erreur ou le retrait de l’exemplaire PDF de la consultation publique en écrivant à l’EVC : {{ implode(' / ', config('evc.contact_emails')) }}.</p>
    </div>
@endsection
