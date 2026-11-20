@php $s = \App\Support\FrenchText::signatory($c['details']['signataire'] ?? 'dg'); @endphp
<div class="abot"><table><tr>
    <td style="width:21mm">@include('documents.partials.qr', ['pos' => 'position:relative', 'size' => '19mm'])</td>
    <td class="date">Fait à Abidjan, le <b>{{ $c['issued'] ? \App\Support\FrenchText::date($c['issued']) : '… (date de délivrance)' }}</b></td>
    <td style="text-align:right"><div class="asig" style="margin-left:auto"><div class="t">{{ $s['feminine'] ? 'La' : 'Le' }} {{ $s['title'] }}</div>@include('documents.partials.signature', ['signataire' => $c['details']['signataire'] ?? 'dg', 'stamp' => true])<div class="line"></div><div class="who">{{ mb_strtoupper($s['name']) }}</div><div class="role">{{ $s['title'] }}</div></div></td>
</tr></table></div>
