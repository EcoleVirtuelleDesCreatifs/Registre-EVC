@use('App\Support\FrenchText', 'F')
@php
    $d = $c['details']; $a = F::agreements($d['civilite'] ?? 'M');
    $missions = F::missions($d['missions'] ?? '');
    $many = count($missions) > 6 || array_sum(array_map(fn ($m) => mb_strlen($m['t']) + mb_strlen($m['d']), $missions)) > 1250;
@endphp
<div class="dp p"><img class="bg" src="{{ $asset('bg-portrait-hd.jpg') }}" alt="">
    @include('documents.partials.micro', ['label' => 'ATTESTATION DE FIN DE STAGE', 'pos' => 'left:12.6mm;width:184.8mm;top:12.4mm'])
    @include('documents.partials.micro', ['label' => 'ATTESTATION DE FIN DE STAGE', 'pos' => 'left:12.6mm;width:184.8mm;bottom:12.4mm'])
    <div class="ac">
        @include('documents.partials.head', ['title' => 'ATTESTATION DE FIN DE STAGE', 'sub' => null])
        <div class="pp">@include('documents.partials.soussigne')</div>
        <div class="nm b" style="font-size:{{ F::nameSize($c['name'], ['20pt', '17pt', '15pt']) }}">{{ $a['civ'] }} {{ $c['name'] }}</div>
        <div class="pp" style="margin-top:2.5mm">a effectué un stage au sein de notre établissement dans les conditions suivantes :</div>
        <table class="card">
            <tr><td class="k">Poste</td><td>{{ $d['poste'] ?? '…' }}</td></tr>
            <tr><td class="k">Période</td><td>Du {{ F::date($d['debut'] ?? null) }} au {{ F::date($d['fin'] ?? null) }}</td></tr>
            <tr><td class="k">Durée</td><td>{{ F::duration($d['debut'] ?? null, $d['fin'] ?? null) ?: '…' }}</td></tr>
        </table>
        @if ($missions)
            <div class="mt">Missions effectuées durant le stage</div>
            <div class="intro">{{ $a['Le'] }} stagiaire a pris part aux activités de l’établissement à travers les missions suivantes :</div>
            <table class="ms">@foreach ($missions as $i => $m)<tr><td class="n">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td><td @if ($many) style="font-size:8.6pt" @endif>@if ($m['t'])<b>{{ $m['t'] }}</b> : @endif{{ $m['d'] }}</td></tr>@endforeach</table>
        @endif
        <div class="formula">{{ $d['appreciation'] ?? F::defaultAppreciation($d['civilite'] ?? 'M') }} En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</div>
    </div>
    @include('documents.partials.bottom')
    @include('documents.partials.specimen')
</div>
