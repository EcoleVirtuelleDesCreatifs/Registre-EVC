@extends('emails.layout')
@php $ceremonie = config('evc.ceremonie'); @endphp
@section('title', 'Félicitations '.$document->holder?->full_name.' !')
@section('action', 'Vérifier mon document')
@section('action-url', $url)
@section('content')
    <p style="font-size:15px;line-height:1.6;margin:0 0 18px">Vous avez validé le <strong>{{ $document->type->label }}</strong>@if ($document->formationLabel()) en <strong>{{ $document->formationLabel() }}</strong>@endif
    de l'École Virtuelle des Créatifs.</p>

    <table style="width:100%;border-collapse:collapse;background:#f6efe0;border-radius:8px;font-size:14px">
        <tr>
            <td style="padding:10px 16px;color:#7a7258;width:140px">Référence</td>
            <td style="padding:10px 16px"><strong style="font-family:monospace">{{ $document->reference }}</strong></td>
        </tr>
        <tr>
            <td style="padding:10px 16px;color:#7a7258;border-top:1px solid #e7ddc8">Date de délivrance</td>
            <td style="padding:10px 16px;border-top:1px solid #e7ddc8">{{ $document->issuedFr() }}</td>
        </tr>
    </table>

    @if ($document->type->is_certificate)
        <h2 style="font-size:17px;color:#0f1d3d;margin:26px 0 10px">Remise de votre certificat : présence obligatoire</h2>
        <p style="font-size:15px;line-height:1.6;margin:0 0 14px">Votre certificat vous sera remis <strong>exclusivement en main propre</strong>, lors de la cérémonie officielle de remise des certificats{{ $ceremonie['lieu'] ? ' à '.$ceremonie['lieu'] : '' }}@if ($ceremonie['date']), prévue le <strong>{{ $ceremonie['date'] }}</strong>@endif.</p>

        <div style="padding:12px 16px;border-left:3px solid #b8893a;background:#fbf7ec;font-size:14px;line-height:1.6;margin-bottom:14px">
            <strong>Condition de délivrance.</strong> Conformément au règlement de certification de l'École Virtuelle des Créatifs, tout titulaire résidant à Abidjan ou ailleurs en Côte d'Ivoire est tenu d'être présent à la cérémonie pour recevoir son certificat. En conséquence :
            <ul style="margin:8px 0 0;padding-left:20px">
                <li>aucun certificat, numérique ou physique, ne sera transmis, envoyé ou rendu accessible avant la cérémonie ;</li>
                <li>aucune copie, aucun duplicata et aucun téléchargement ne seront délivrés par anticipation ;</li>
                <li>en cas d'absence, le certificat reste conservé par l'École et ne sera remis qu'à la prochaine cérémonie de remise.</li>
            </ul>
        </div>

        <p style="font-size:15px;line-height:1.6;margin:0 0 14px"><strong>En cas d'empêchement.</strong> Vous pouvez vous faire représenter à la cérémonie par une personne de votre choix. Celle-ci devra présenter une procuration écrite et signée de votre main, une copie de votre pièce d'identité et sa propre pièce d'identité. Sans ces documents, le certificat ne lui sera pas remis.</p>

        <p style="font-size:15px;line-height:1.6;margin:0 0 14px">Aucune autre dérogation ne sera accordée. Seuls les titulaires résidant <strong>hors de Côte d'Ivoire</strong> recevront leur certificat par e-mail ou pourront le télécharger sur la plateforme du registre officiel.</p>

        <p style="font-size:15px;line-height:1.6;margin:0 0 14px">La date, l'heure et le lieu de la cérémonie vous seront communiqués par e-mail. Prenez dès maintenant vos dispositions pour être présent(e) ou représenté(e).</p>

        <h2 style="font-size:17px;color:#0f1d3d;margin:26px 0 10px">Vérification de votre titre</h2>
        <p style="font-size:15px;line-height:1.6;margin:0 0 14px">Votre certification est d'ores et déjà inscrite au registre officiel de l'École. Cette inscription permet à un tiers d'en vérifier l'authenticité. Pour les titulaires résidant en Côte d'Ivoire, elle ne donne pas accès au certificat et ne le remplace pas.</p>
    @else
        <p style="font-size:15px;line-height:1.6;margin:18px 0 0">Votre document est enregistré au registre officiel et son authenticité peut être vérifiée à tout moment, par vous-même ou par tout employeur ou institution :</p>
    @endif

    <p style="font-size:13px;color:#7a7258;line-height:1.6;margin:22px 0 0">Conservez votre référence <strong>{{ $document->reference }}</strong> : elle vous sera demandée le jour de la cérémonie et permet d'authentifier votre titre sur le registre officiel.</p>
@endsection
