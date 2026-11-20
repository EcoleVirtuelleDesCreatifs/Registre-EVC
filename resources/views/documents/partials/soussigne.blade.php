@php $s = \App\Support\FrenchText::signatory($c['details']['signataire'] ?? 'dg'); @endphp
Je soussigné{{ $s['feminine'] ? 'e' : '' }}, <b>{{ $s['name'] }}</b>, {{ $s['title'] }} de l’École Virtuelle des Créatifs (EVC), atteste que :
