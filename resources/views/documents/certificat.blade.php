@use('App\Support\FrenchText', 'F')
@php
    $dg = config('evc.signataires.dg'); $dp = config('evc.signataires.dp');
    $ref = $c['reference'] ?: 'attribuée à la délivrance';
@endphp
<div class="dp l"><img class="bg" src="{{ $asset('bg-paysage-hd.jpg') }}" alt="">
    @include('documents.partials.micro', ['label' => 'CERTIFICAT AUTHENTIQUE', 'pos' => 'left:13.6mm;width:269.8mm;top:13.4mm'])
    @include('documents.partials.micro', ['label' => 'CERTIFICAT AUTHENTIQUE', 'pos' => 'left:13.6mm;width:269.8mm;bottom:13.4mm'])
    <div class="cc"><img class="logo" src="{{ $asset('logo-evc.png') }}" alt="EVC">
        <div class="inst b">École Virtuelle des Créatifs</div><div class="place b">Abidjan · République de Côte d’Ivoire</div>
        <div class="lead">Le Directeur Général de l’École Virtuelle des Créatifs, sur proposition du jury pédagogique<br>et après validation de l’ensemble des évaluations, confère à</div>
        <div class="name b" style="font-size:{{ F::nameSize($c['name'], ['38pt', '32pt', '27pt']) }}">{{ $c['name'] }}</div>
        <div class="lead2">le</div><div class="title b">Certificat Professionnel</div>
        <div class="prog b">en {{ $c['formationLabel'] ?: '…' }}</div>
        @if (! empty($c['details']['mention']))<div class="mention">{{ $c['details']['mention'] }}</div>@endif
        <div class="body">avec tous les droits, privilèges et prérogatives qui s’y rattachent.</div>
        <div class="formula">En foi de quoi, le présent certificat, revêtu du sceau de l’École et des signatures des autorités habilitées, lui est délivré.<br>Fait à Abidjan, {{ $c['issued'] ? F::solemnDate($c['issued']) : 'le … (date de délivrance)' }}.</div>
    </div>
    <div class="csig" style="left:38mm">@include('documents.partials.signature', ['signataire' => 'dg', 'stamp' => true])<div class="line"></div><div class="who b">{{ mb_strtoupper($dg['name']) }}</div><div class="role">{{ $dg['title'] }}</div></div>
    <div class="seal"><img src="{{ $asset('sceau.png') }}" alt=""><div class="yr">PROMO {{ $c['year'] }}</div></div>
    <div class="csig" style="right:38mm">@include('documents.partials.signature', ['signataire' => 'dp'])<div class="line"></div><div class="who b">{{ mb_strtoupper($dp['name']) }}</div><div class="role">{{ $dp['title'] }}</div></div>
    <div class="cnum"><span>Certificat n° <b>{{ $ref }}</b></span></div>
    @include('documents.partials.qr', ['pos' => 'right:20mm;top:19mm', 'size' => '18mm'])
    @include('documents.partials.specimen')
</div>
