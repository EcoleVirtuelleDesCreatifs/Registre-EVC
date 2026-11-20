@use('App\Support\FrenchText', 'F')
@php
    $d = $c['details']; $s = F::signatory($d['signataire'] ?? 'dg');
    $body = trim($d['corps'] ?? '') !== '' ? $d['corps'] : F::recommendation($c['name'], $d['civilite'] ?? 'M', $d['signataire'] ?? 'dg', $c['formationLabel'], $c['certificate']);
    $paras = F::paragraphs($body);
    $small = mb_strlen(implode(' ', $paras)) > 2300;
    $lh = config('evc.letterhead');
@endphp
<div class="dp p lt"><img class="bg" src="{{ $asset('bg-lettre-hd.jpg') }}" alt="">
    <div class="lh"><table><tr><td><img src="{{ $asset('logo-evc.png') }}" style="height:15mm" alt="EVC"></td><td><div class="inst b">École Virtuelle des Créatifs</div><div class="sub">{{ $s['service'] }} · Abidjan, Côte d’Ivoire</div></td></tr></table></div>
    <div class="lmeta"><table><tr><td>Réf. <b>{{ $c['reference'] ?: 'attribuée à la délivrance' }}</b></td><td style="text-align:right">Abidjan, le <b>{{ $c['issued'] ? F::date($c['issued']) : '… (date de délivrance)' }}</b></td></tr></table></div>
    <div class="lbody">
        <div class="obj">Objet : <span>Lettre de recommandation</span> · {{ $c['name'] }}</div>
        <div class="to">{{ $d['destinataire'] ?? 'À l’attention des recruteurs et de toute personne concernée,' }}</div>
        @foreach ($paras as $p)<p @if ($small) style="font-size:9.6pt;line-height:1.5" @endif>{{ $p }}</p>@endforeach
        <div class="closing">Je vous prie d’agréer, Madame, Monsieur, l’expression de mes salutations distinguées.</div>
        <div class="lsig"><div class="who">{{ mb_strtoupper($s['name']) }}</div><div class="role">{{ $s['title'] }}</div><div class="role">École Virtuelle des Créatifs</div></div>
    </div>
    <div class="lfoot"><table><tr>
        <td style="width:22mm">@include('documents.partials.qr', ['pos' => 'position:relative', 'size' => '15mm', 'caption' => false])</td>
        <td>{{ $lh[0] }}<br>{{ $lh[1] }}</td>
        <td style="text-align:right">Authenticité vérifiable sur<br><b style="color:#1c2557">{{ config('evc.verification_host') }}</b></td>
    </tr></table></div>
    @include('documents.partials.specimen')
</div>
