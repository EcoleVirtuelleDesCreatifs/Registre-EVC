@extends('layouts.admin')
@section('title', 'Tableau de bord')
@section('admin')
    <h1>Tableau de bord</h1>
    <div class="stats">
        <div><b>{{ $counts['publies'] }}</b>Documents délivrés et publiés</div>
        <div><b>{{ $counts['a_controler'] }}</b>À contrôler (pédagogie)</div>
        <div><b>{{ $counts['a_approuver'] }}</b>En attente d’approbation</div>
        <div><b>{{ $counts['a_delivrer'] }}</b>Approuvés à délivrer / publier</div>
        <div><b>{{ $counts['brouillons'] }}</b>Brouillons</div>
        <div><b style="{{ $counts['rejetes'] ? 'color:var(--bad)' : '' }}">{{ $counts['rejetes'] }}</b>Rejetés</div>
        <div><b>{{ $counts['revoques'] }}</b>Révoqués</div>
        <div><b style="{{ $counts['sans_pdf'] ? 'color:var(--warn)' : '' }}">{{ $counts['sans_pdf'] }}</b>Publiés sans PDF</div>
    </div>

    <div class="cols">
        <div class="card">
            <h2>Documents délivrés par formation</h2>
            @php $maxF = max(1, $byFormation->max() ?? 1); @endphp
            <div class="bars">
                @foreach (config('evc.formations') as $code => $label)
                    @php $n = $byFormation[$code] ?? 0; @endphp
                    <div class="bar"><span>{{ $label }}</span><span class="t"><i style="width:{{ round($n / $maxF * 100) }}%"></i></span><b>{{ $n }}</b></div>
                @endforeach
                @php $sans = $byFormation->filter(fn ($n, $k) => blank($k))->sum(); @endphp
                @if ($sans)
                    <div class="bar"><span>Sans formation (stages, lettres)</span><span class="t"><i style="width:{{ round($sans / $maxF * 100) }}%"></i></span><b>{{ $sans }}</b></div>
                @endif
            </div>

            <h2 style="margin-top:22px">Par type de document</h2>
            @php $maxT = max(1, $byType->max() ?? 1); @endphp
            <div class="bars">
                @foreach ($types as $id => $type)
                    @php $n = $byType[$id] ?? 0; @endphp
                    <div class="bar"><span>{{ $type->label }}</span><span class="t"><i style="width:{{ round($n / $maxT * 100) }}%"></i></span><b>{{ $n }}</b></div>
                @endforeach
            </div>

            <h2 style="margin-top:22px">Délivrances {{ now()->year }}, par mois</h2>
            @php $maxM = max(1, $months->max() ?? 1); @endphp
            <div class="month">@for ($m = 1; $m <= 12; $m++)<div style="height:{{ round(($months[$m] ?? 0) / $maxM * 100) }}%" title="{{ $months[$m] ?? 0 }}"></div>@endfor</div>
            <small class="month" style="height:auto">@foreach (['J','F','M','A','M','J','J','A','S','O','N','D'] as $l)<span>{{ $l }}</span>@endforeach</small>
        </div>

        <div>
            <div class="card">
                <h2>Par année</h2>
                @forelse ($years as $year => $n)
                    <div class="row" style="justify-content:space-between;border-bottom:1px solid #f0e9da;padding:6px 0"><span>{{ $year }}</span><b>{{ $n }}</b></div>
                @empty <p class="muted">Aucune délivrance.</p> @endforelse
            </div>
            <div class="card" style="margin-top:16px">
                <h2>Vérifications publiques (30 jours)</h2>
                <p class="small" style="margin:-4px 0 10px"><a href="{{ route('admin.stats.visits') }}">Voir les statistiques détaillées du site →</a></p>
                <div class="kv">
                    <span class="k">Total</span><span class="v">{{ $verif['total30'] }}</span>
                    <span class="k">Valides</span><span class="v">{{ $verif['valides30'] }}</span>
                    <span class="k">Introuvables</span><span class="v" style="{{ $verif['introuvables30'] ? 'color:var(--bad)' : '' }}">{{ $verif['introuvables30'] }}</span>
                    <span class="k">Par QR code</span><span class="v">{{ $verif['qr30'] }}</span>
                </div>
                @if ($unknown->count())
                    <p class="small" style="margin-bottom:4px">Références inconnues les plus recherchées (possibles faux) :</p>
                    @foreach ($unknown as $u)<div class="fileinfo">{{ $u->query }} · {{ $u->n }} fois</div>@endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
