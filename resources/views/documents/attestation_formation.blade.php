@use('App\Support\FrenchText', 'F')
@php
    $d = $c['details']; $a = F::agreements($d['civilite'] ?? 'M'); $st = $d['statut'] ?? 'en_cours';
    $statuts = config('evc.statuts_formation');
    $rows = array_filter([
        ['Formation', e($c['formationLabel'] ?: '…')],
        ! empty($d['contenu']) ? ['Contenu', e($d['contenu'])] : null,
        ['Date d’inscription', F::date($d['inscription'] ?? null)],
        $st === 'achevee' ? ['Fin de formation', F::date($d['fin_formation'] ?? null)] : null,
        ! empty($d['format']) ? ['Format', e($d['format'])] : null,
        ['Statut', '<b class="gold">'.e($statuts[$st] ?? $st).'</b>'],
    ]);
@endphp
<div class="dp p"><img class="bg" src="{{ $asset('bg-portrait-hd.jpg') }}" alt="">
    @include('documents.partials.micro', ['label' => 'ATTESTATION DE FORMATION', 'pos' => 'left:12.6mm;width:184.8mm;top:12.4mm'])
    @include('documents.partials.micro', ['label' => 'ATTESTATION DE FORMATION', 'pos' => 'left:12.6mm;width:184.8mm;bottom:12.4mm'])
    <div class="ac">
        @include('documents.partials.head', ['title' => 'ATTESTATION DE FORMATION', 'sub' => $st === 'achevee' ? 'Formation achevée' : ($st === 'finalisation' ? 'Formation en cours de finalisation' : 'Formation en cours')])
        <div class="pp" style="margin-top:6mm">@include('documents.partials.soussigne')</div>
        <div class="nm b" style="font-size:{{ F::nameSize($c['name'], ['20pt', '17pt', '15pt']) }}">{{ $a['civ'] }} {{ $c['name'] }}</div>
        <div class="pp">
            @if ($st === 'achevee')
                a suivi avec assiduité, au sein de notre établissement, du <b>{{ F::date($d['inscription'] ?? null) }}</b> au <b>{{ F::date($d['fin_formation'] ?? null) }}</b>, la formation professionnelle suivante :
            @elseif ($st === 'finalisation')
                est régulièrement inscrit{{ $a['e'] }} au sein de notre établissement depuis le <b>{{ F::date($d['inscription'] ?? null) }}</b> et achève actuellement la formation professionnelle suivante, parvenue à sa phase finale :
            @else
                est régulièrement inscrit{{ $a['e'] }} au sein de notre établissement depuis le <b>{{ F::date($d['inscription'] ?? null) }}</b> et y suit actuellement la formation professionnelle suivante :
            @endif
        </div>
        <table class="card">@foreach ($rows as [$k, $v])<tr><td class="k">{{ $k }}</td><td>{!! $v !!}</td></tr>@endforeach</table>
        <div class="note">
            @if ($st === 'achevee')
                La présente attestation certifie le suivi de la formation.
            @else
                La présente attestation certifie l’inscription et le suivi de la formation{{ $st === 'finalisation' ? ' jusqu’à sa phase finale' : '' }}.
            @endif
            Elle ne vaut pas certificat professionnel, celui-ci n’étant délivré qu’après validation de l’ensemble des évaluations et avis du jury pédagogique.
        </div>
        <div class="formula">En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.</div>
    </div>
    @include('documents.partials.bottom')
    @include('documents.partials.specimen')
</div>
