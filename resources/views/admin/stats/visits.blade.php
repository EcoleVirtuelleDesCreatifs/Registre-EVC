@extends('layouts.admin')
@section('title', 'Statistiques de consultation')

@push('head')
<style>
    :root{--s1:#3d4a9e;--s2:#b8893a}
    .vs-head{display:flex;justify-content:space-between;align-items:flex-end;gap:14px;flex-wrap:wrap;margin-bottom:18px}
    .vs-head .lead{margin:0}
    .vs-seg{display:inline-flex;border:1.5px solid var(--line);border-radius:10px;overflow:hidden;background:#fff}
    .vs-seg a{padding:8px 14px;font-size:13.5px;text-decoration:none;color:var(--navy);border-right:1px solid var(--line)}
    .vs-seg a:last-child{border-right:0}
    .vs-seg a.on{background:var(--navy);color:#fff}

    .vs-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px}
    .vs-kpi{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px 16px;position:relative;overflow:hidden}
    .vs-kpi .k{font-size:12.5px;color:var(--muted)}
    .vs-kpi b{display:block;font-family:"Libre Baskerville",serif;font-weight:400;font-size:28px;color:var(--navy);line-height:1.2;margin:4px 0 2px;font-variant-numeric:tabular-nums}
    .vs-kpi .d{font-size:12px;color:var(--muted)}
    .vs-kpi .d.up{color:var(--ok)}.vs-kpi .d.down{color:var(--bad)}
    .vs-kpi:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--gold)}
    .vs-kpi.main:before{background:var(--s1)}

    .vs-grid{display:grid;gap:16px;margin-bottom:16px}
    .vs-g2{grid-template-columns:1fr 1fr}.vs-g3{grid-template-columns:repeat(3,1fr)}.vs-g21{grid-template-columns:1.6fr 1fr}
    .vs-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 20px;min-width:0}
    .vs-card h2{font-family:"Libre Baskerville",serif;font-weight:400;font-size:17px;color:var(--navy);margin:0 0 4px}
    .vs-card .sub{font-size:12.5px;color:var(--muted);margin:0 0 14px}
    .vs-none{color:var(--muted);font-size:13.5px;margin:8px 0}

    .vs-legend{display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:var(--ink);margin-bottom:6px;align-items:center}
    .vs-legend i{display:inline-block;width:14px;height:3px;border-radius:2px;margin-right:6px;vertical-align:middle}
    .vs-legend button{margin-left:auto;font:inherit;font-size:12.5px;border:1px solid var(--line);background:#fff;border-radius:8px;padding:4px 10px;color:var(--navy);cursor:pointer}
    #vs-chart{position:relative;width:100%;height:280px}
    #vs-chart svg{display:block;width:100%;height:100%;overflow:visible}
    .vs-axis{font-size:11px;fill:var(--muted);font-family:Lora,Georgia,serif}
    .vs-endlbl{font-size:11.5px;font-weight:600;fill:var(--ink)}
    .vs-table{display:none;max-height:300px;overflow:auto;margin-top:8px}
    .vs-table.on{display:block}
    .vs-table table td,.vs-table table th{padding:6px 10px}

    .vs-hbars{display:flex;flex-direction:column;gap:9px}
    .vs-hbar{display:grid;grid-template-columns:minmax(90px,1.1fr) 1.4fr 48px 38px;gap:10px;align-items:center;font-size:13.5px;padding:2px 0;cursor:default}
    .vs-hbar .l{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .vs-hbar .ico{margin-right:6px}
    .vs-hbar .t{height:10px;background:#f3eee2;border-radius:5px;overflow:hidden}
    .vs-hbar .t i{display:block;height:100%;background:var(--s1);border-radius:0 4px 4px 0}
    .vs-hbar b{text-align:right;font-variant-numeric:tabular-nums;color:var(--navy);font-weight:600}
    .vs-hbar .p{text-align:right;font-size:12px;color:var(--muted);font-variant-numeric:tabular-nums}
    .vs-hbar:hover .t i{filter:brightness(1.15)}

    .vs-cols{display:flex;align-items:flex-end;gap:3px;height:150px;padding-top:6px;border-bottom:1px solid var(--line)}
    .vs-cols div{flex:1;display:flex;align-items:flex-end;height:100%;cursor:default}
    .vs-cols div i{display:block;width:100%;background:var(--s2);border-radius:4px 4px 0 0;min-height:2px}
    .vs-cols div:hover i{background:var(--s1)}
    .vs-cols.wd{gap:10px}
    .vs-ticks{display:flex;gap:3px;font-size:10.5px;color:var(--muted);margin-top:4px}
    .vs-ticks span{flex:1;text-align:center}
    .vs-ticks.wd{gap:10px;font-size:12px}
    .vs-peak{font-size:12.5px;color:var(--muted);margin-top:10px}

    .vs-split{display:flex;height:14px;border-radius:7px;overflow:hidden;gap:2px;margin:6px 0 12px}
    .vs-split span{display:block;height:100%}
    .vs-list{list-style:none;margin:0;padding:0;font-size:13.5px}
    .vs-list li{display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #f4efe4}
    .vs-list li:last-child{border-bottom:0}
    .vs-list .dot{width:10px;height:10px;border-radius:50%;flex:none}
    .vs-list b{margin-left:auto;font-variant-numeric:tabular-nums;color:var(--navy)}
    .vs-list small{color:var(--muted);width:42px;text-align:right}

    .vs-card table{font-size:13.5px}
    .vs-card table td,.vs-card table th{padding:7px 8px}
    .vs-alert{border:1.5px solid #f0c9c5;background:#fff7f6}
    .vs-alert h2{color:var(--bad)}

    #vs-tip{position:fixed;z-index:50;pointer-events:none;background:var(--navy);color:#fff;font-size:12.5px;line-height:1.45;padding:8px 11px;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.18);opacity:0;transition:opacity .1s;max-width:260px}
    #vs-tip.on{opacity:1}
    #vs-tip strong{display:block;color:var(--gold-l);font-weight:600;margin-bottom:2px}
    #vs-tip .sw{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:6px;vertical-align:middle;box-shadow:0 0 0 1.5px #fff}
    .vs-foot{font-size:12.5px;color:var(--muted);margin-top:8px}

    @media (max-width:1000px){.vs-g3{grid-template-columns:1fr 1fr}.vs-g21{grid-template-columns:1fr}}
    @media (max-width:760px){.vs-g2,.vs-g3{grid-template-columns:1fr}#vs-chart{height:220px}}
    @media print{.top,.side,.vs-seg,.vs-legend button{display:none!important}.adm{display:block}.adm main{padding:0;max-width:none}.vs-card,.vs-kpi{break-inside:avoid}}
</style>
@endpush

@section('admin')
    @php
        $fmt = fn ($n) => number_format((int) $n, 0, ',', ' ');
        $delta = function ($a, $b, $suffix = ' %') {
            if ($b === null || $a === null) return ['', '—'];
            if ($suffix === ' pt') { $d = $a - $b; return [$d > 0 ? 'up' : ($d < 0 ? 'down' : ''), ($d > 0 ? '▲ +' : ($d < 0 ? '▼ ' : '= ')).$d.' pt'];}
            if ((int) $b === 0) return [$a > 0 ? 'up' : '', $a > 0 ? 'nouveau' : '='];
            $d = round(($a - $b) / $b * 100);
            return [$d > 0 ? 'up' : ($d < 0 ? 'down' : ''), ($d > 0 ? '▲ +' : ($d < 0 ? '▼ ' : '= ')).$d.' %'];
        };
        $kpis = [
            ['Pages vues', $now['pages'], $prev['pages'], true],
            ['Visiteurs uniques', $now['visiteurs'], $prev['visiteurs'], true],
            ['Vérifications', $now['verifications'], $prev['verifications'], false],
            ['Documents valides', $now['taux'] === null ? null : $now['taux'], $prev['taux'], false, ' %'],
            ['Scans de QR code', $now['qr'], $prev['qr'], false],
            ['PDF officiels consultés', $now['pdf'], $prev['pdf'], false],
        ];
        $resultMeta = [
            'valide' => ['Document valide', 'var(--ok)', '✓'],
            'revoque' => ['Document révoqué', '#2b2b2b', '⊘'],
            'introuvable' => ['Référence introuvable', 'var(--bad)', '✕'],
        ];
        $methodMeta = ['reference' => ['Saisie de la référence', 'var(--s1)'], 'qr' => ['Scan du QR code', 'var(--s2)'], 'api' => ['API My EVC', 'var(--muted)']];
        $deviceIcon = ['ordinateur' => '🖥', 'mobile' => '📱', 'tablette' => '📲'];
        $langNames = ['fr' => 'Français', 'en' => 'Anglais', 'es' => 'Espagnol', 'pt' => 'Portugais', 'de' => 'Allemand', 'ar' => 'Arabe', 'zh' => 'Chinois', 'it' => 'Italien'];
        $peakH = $hours->search($hours->max());
        $peakW = $weekdays->search($weekdays->max());
        $totalVerif = max(1, $results->sum());
        $totalMeth = max(1, $methods->sum());
    @endphp

    <div class="vs-head">
        <div>
            <h1>Statistiques de consultation</h1>
            <p class="lead">Registre public <strong>verification.ecolevirtuelledescreatifs.com</strong> · du {{ $from->format('d/m/Y') }} au {{ $to->format('d/m/Y') }}</p>
        </div>
        <nav class="vs-seg" aria-label="Période">
            @foreach (\App\Http\Controllers\Admin\VisitStatsController::PERIODS as $k => $label)
                <a href="{{ route('admin.stats.visits', ['periode' => $k]) }}" class="{{ (int) $k === $days ? 'on' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    {{-- Indicateurs clés --}}
    <div class="vs-kpis">
        @foreach ($kpis as $i => $k)
            @php [$cls, $txt] = $delta($k[1], $k[2], ($k[4] ?? null) === ' %' ? ' pt' : ' %'); @endphp
            <div class="vs-kpi {{ $k[3] ? 'main' : '' }}">
                <div class="k">{{ $k[0] }}</div>
                <b>{{ $k[1] === null ? '—' : $fmt($k[1]).($k[4] ?? '') }}</b>
                <div class="d {{ $cls }}" title="Période précédente : {{ $k[2] === null ? '—' : $fmt($k[2]).($k[4] ?? '') }}">{{ $txt }} <span style="color:var(--muted)">vs période préc.</span></div>
            </div>
        @endforeach
    </div>

    {{-- Évolution --}}
    <div class="vs-card" style="margin-bottom:16px">
        <h2>Fréquentation {{ $monthly ? 'mois par mois' : 'jour par jour' }}</h2>
        <div class="vs-legend">
            <span><i style="background:var(--s1)"></i>Pages vues</span>
            <span><i style="background:var(--s2)"></i>Visiteurs uniques</span>
            <button type="button" id="vs-toggle" aria-expanded="false">Voir le tableau</button>
        </div>
        <div id="vs-chart" role="img" aria-label="Évolution des pages vues et des visiteurs uniques"></div>
        <div class="vs-table" id="vs-table">
            <table>
                <thead><tr><th>{{ $monthly ? 'Mois' : 'Jour' }}</th><th style="text-align:right">Pages vues</th><th style="text-align:right">Visiteurs</th><th style="text-align:right">Vérifications</th></tr></thead>
                <tbody>
                @foreach (array_reverse($series) as $s)
                    <tr><td>{{ $s['label'] }}</td><td class="num" style="text-align:right">{{ $fmt($s['pages']) }}</td><td class="num" style="text-align:right">{{ $fmt($s['visiteurs']) }}</td><td class="num" style="text-align:right">{{ $fmt($s['verifications']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vérifications --}}
    <div class="vs-grid vs-g2">
        <div class="vs-card">
            <h2>Résultats des vérifications</h2>
            <p class="sub">{{ $fmt($results->sum()) }} vérification(s) en ligne sur la période</p>
            @if ($results->sum())
                <div class="vs-split" aria-hidden="true">
                    @foreach ($resultMeta as $code => [$label, $color])
                        @if ($results[$code] ?? 0)<span style="width:{{ $results[$code] / $totalVerif * 100 }}%;background:{{ $color }}" data-tip="{{ $label }}|{{ $fmt($results[$code]) }} · {{ round($results[$code] / $totalVerif * 100) }} %"></span>@endif
                    @endforeach
                </div>
                <ul class="vs-list">
                    @foreach ($resultMeta as $code => [$label, $color, $icon])
                        <li><span class="dot" style="background:{{ $color }}"></span>{{ $icon }} {{ $label }}<b>{{ $fmt($results[$code] ?? 0) }}</b><small>{{ round(($results[$code] ?? 0) / $totalVerif * 100) }} %</small></li>
                    @endforeach
                </ul>
            @else
                <p class="vs-none">Aucune vérification sur la période.</p>
            @endif
        </div>
        <div class="vs-card">
            <h2>Mode d’accès</h2>
            <p class="sub">Comment les visiteurs arrivent jusqu’au document</p>
            @if ($methods->sum())
                <div class="vs-split" aria-hidden="true">
                    @foreach ($methodMeta as $code => [$label, $color])
                        @if ($methods[$code] ?? 0)<span style="width:{{ $methods[$code] / $totalMeth * 100 }}%;background:{{ $color }}" data-tip="{{ $label }}|{{ $fmt($methods[$code]) }} · {{ round($methods[$code] / $totalMeth * 100) }} %"></span>@endif
                    @endforeach
                </div>
                <ul class="vs-list">
                    @foreach ($methodMeta as $code => [$label, $color])
                        @if ($code !== 'api' || ($methods[$code] ?? 0))
                            <li><span class="dot" style="background:{{ $color }}"></span>{{ $label }}<b>{{ $fmt($methods[$code] ?? 0) }}</b><small>{{ round(($methods[$code] ?? 0) / $totalMeth * 100) }} %</small></li>
                        @endif
                    @endforeach
                </ul>
            @else
                <p class="vs-none">Aucune vérification sur la période.</p>
            @endif
        </div>
    </div>

    {{-- Moments --}}
    <div class="vs-grid vs-g21">
        <div class="vs-card">
            <h2>Heures de consultation</h2>
            <p class="sub">Pages vues selon l’heure (heure d’Abidjan)</p>
            @php $mh = max(1, $hours->max()); @endphp
            <div class="vs-cols">
                @foreach ($hours as $h => $n)<div data-tip="{{ sprintf('%02d h – %02d h', $h, ($h + 1) % 24) }}|{{ $fmt($n) }} pages vues"><i style="height:{{ $n / $mh * 100 }}%"></i></div>@endforeach
            </div>
            <div class="vs-ticks">@foreach ($hours as $h => $n)<span>{{ $h % 3 === 0 ? $h.'h' : '' }}</span>@endforeach</div>
            @if ($hours->sum())<p class="vs-peak">Heure la plus active : <strong>{{ sprintf('%02d h – %02d h', $peakH, ($peakH + 1) % 24) }}</strong></p>@endif
        </div>
        <div class="vs-card">
            <h2>Jours de la semaine</h2>
            <p class="sub">Pages vues selon le jour</p>
            @php $mw = max(1, $weekdays->max()); @endphp
            <div class="vs-cols wd">
                @foreach ($weekdays as $d => $n)<div data-tip="{{ $d }}|{{ $fmt($n) }} pages vues"><i style="height:{{ $n / $mw * 100 }}%"></i></div>@endforeach
            </div>
            <div class="vs-ticks wd">@foreach ($weekdays as $d => $n)<span>{{ $d }}</span>@endforeach</div>
            @if ($weekdays->sum())<p class="vs-peak">Jour le plus actif : <strong>{{ $peakW }}</strong></p>@endif
        </div>
    </div>

    {{-- Visiteurs --}}
    <div class="vs-grid vs-g3">
        <div class="vs-card">
            <h2>Appareils</h2>
            <p class="sub">Type d’écran utilisé</p>
            @include('admin.stats.partials.hbars', ['items' => $devices->map(fn ($r) => ['label' => ucfirst($r->k ?? 'inconnu'), 'icon' => $deviceIcon[$r->k] ?? null, 'n' => $r->n, 'sub' => $fmt($r->v).' visiteur(s)'])->all()])
        </div>
        <div class="vs-card">
            <h2>Navigateurs</h2>
            <p class="sub">Logiciel de navigation</p>
            @include('admin.stats.partials.hbars', ['items' => $browsers->map(fn ($r) => ['label' => $r->k ?? 'Autre', 'n' => $r->n, 'sub' => $fmt($r->v).' visiteur(s)'])->all()])
        </div>
        <div class="vs-card">
            <h2>Systèmes</h2>
            <p class="sub">Système d’exploitation</p>
            @include('admin.stats.partials.hbars', ['items' => $systems->map(fn ($r) => ['label' => $r->k ?? 'Autre', 'n' => $r->n, 'sub' => $fmt($r->v).' visiteur(s)'])->all()])
        </div>
    </div>

    <div class="vs-grid vs-g3">
        <div class="vs-card">
            <h2>Provenance</h2>
            <p class="sub">Site d’où arrivent les visiteurs</p>
            @include('admin.stats.partials.hbars', ['items' => collect([['label' => 'Accès direct, QR code, e-mail', 'n' => $direct, 'sub' => 'Lien saisi ou scanné, favori, application']])
                ->merge($sources->map(fn ($r) => ['label' => $r->k, 'n' => $r->n, 'sub' => $fmt($r->v).' visiteur(s)']))->filter(fn ($i) => $i['n'] > 0)->values()->all()])
        </div>
        <div class="vs-card">
            <h2>Pages consultées</h2>
            <p class="sub">Les rubriques du registre public</p>
            @include('admin.stats.partials.hbars', ['items' => $pages->map(fn ($r) => ['label' => \App\Models\SiteVisit::PAGES[$r->k] ?? $r->k, 'n' => $r->n, 'sub' => $fmt($r->v).' visiteur(s)'])->all()])
        </div>
        <div class="vs-card">
            <h2>Langues</h2>
            <p class="sub">Langue du navigateur des visiteurs</p>
            @include('admin.stats.partials.hbars', ['items' => $langs->map(fn ($r) => ['label' => $langNames[$r->k] ?? strtoupper($r->k), 'n' => $r->n, 'sub' => $fmt($r->v).' visiteur(s)'])->all()])
        </div>
    </div>

    {{-- Documents --}}
    <div class="vs-grid vs-g2">
        <div class="vs-card">
            <h2>Documents les plus vérifiés</h2>
            <p class="sub">Les titulaires dont le document a été le plus contrôlé</p>
            @if ($topDocs->isEmpty())<p class="vs-none">Aucun document vérifié sur la période.</p>@else
            <table>
                <thead><tr><th>Référence</th><th>Titulaire</th><th style="text-align:right">Vérif.</th><th>Dernière</th></tr></thead>
                <tbody>
                @foreach ($topDocs as $r)
                    <tr>
                        <td class="num">@if ($r->document)<a href="{{ route('admin.documents.show', $r->document) }}">{{ $r->document->reference }}</a>@else — @endif</td>
                        <td>{{ $r->document?->holder?->full_name ?? '—' }}</td>
                        <td class="num" style="text-align:right"><strong>{{ $fmt($r->n) }}</strong></td>
                        <td class="small" style="margin:0">{{ \Illuminate\Support\Carbon::parse($r->last_at)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
        <div class="vs-card">
            <h2>Références introuvables</h2>
            <p class="sub">Erreurs de saisie ou tentatives de fausses références</p>
            @if ($unknown->isEmpty())<p class="vs-none">Aucune référence introuvable sur la période. 👍</p>@else
            <table>
                <thead><tr><th>Saisie</th><th style="text-align:right">Fois</th><th>Dernière</th></tr></thead>
                <tbody>
                @foreach ($unknown as $u)
                    <tr><td class="num">{{ $u->query }}</td><td class="num" style="text-align:right"><strong>{{ $fmt($u->n) }}</strong></td><td class="small" style="margin:0">{{ \Illuminate\Support\Carbon::parse($u->last_at)->format('d/m/Y H:i') }}</td></tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    @if ($suspects->isNotEmpty())
        <div class="vs-card vs-alert" style="margin-bottom:16px">
            <h2>⚠ Activité inhabituelle</h2>
            <p class="sub">Visiteurs ayant lancé au moins 15 vérifications sur la période : possible recherche automatisée. Chaque visiteur est identifié par une empreinte anonyme.</p>
            <table>
                <thead><tr><th>Visiteur (empreinte)</th><th style="text-align:right">Vérifications</th><th style="text-align:right">Introuvables</th><th>Dernière activité</th></tr></thead>
                <tbody>
                @foreach ($suspects as $s)
                    <tr><td class="num">#{{ strtoupper(substr($s->ip_hash, 0, 8)) }}</td><td class="num" style="text-align:right"><strong>{{ $fmt($s->n) }}</strong></td><td class="num" style="text-align:right;{{ $s->ko > $s->n / 2 ? 'color:var(--bad)' : '' }}">{{ $fmt($s->ko) }}</td><td class="small" style="margin:0">{{ \Illuminate\Support\Carbon::parse($s->last_at)->format('d/m/Y H:i') }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="vs-card">
        <h2>Dernières vérifications</h2>
        <p class="sub">En temps réel, les 12 plus récentes</p>
        @if ($recent->isEmpty())<p class="vs-none">Aucune vérification pour l’instant.</p>@else
        <div style="overflow-x:auto">
        <table>
            <thead><tr><th>Date</th><th>Référence</th><th>Titulaire</th><th>Accès</th><th>Résultat</th></tr></thead>
            <tbody>
            @foreach ($recent as $v)
                <tr>
                    <td class="num">{{ $v->created_at->format('d/m/Y H:i') }}</td>
                    <td class="num">{{ $v->document?->reference ?? $v->query }}</td>
                    <td>{{ $v->document?->holder?->full_name ?? '—' }}</td>
                    <td>{{ $methodMeta[$v->method][0] ?? $v->method }}</td>
                    <td><span style="color:{{ $resultMeta[$v->result][1] ?? 'inherit' }};font-weight:600">{{ $resultMeta[$v->result][2] ?? '' }} {{ $resultMeta[$v->result][0] ?? $v->result }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    <p class="vs-foot">Mesure d’audience interne, sans cookie ni outil tiers : aucune adresse IP n’est conservée (empreinte irréversible), les robots et les membres de l’équipe connectés ne sont pas comptés. Données conservées 13 mois.</p>

    <div id="vs-tip" role="tooltip"></div>
@endsection

@push('scripts')
<script>
(function () {
    const S = @json($series, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    const tip = document.getElementById('vs-tip');
    const nf = new Intl.NumberFormat('fr-FR');

    function showTip(lines, x, y, swatches) {
        tip.textContent = '';
        lines.forEach((l, i) => {
            const el = document.createElement(i === 0 ? 'strong' : 'div');
            if (swatches && swatches[i]) { const s = document.createElement('span'); s.className = 'sw'; s.style.background = swatches[i]; el.appendChild(s); }
            el.appendChild(document.createTextNode(l));
            tip.appendChild(el);
        });
        tip.classList.add('on');
        const r = tip.getBoundingClientRect();
        let left = x + 14, top = y - r.height - 12;
        if (left + r.width > window.innerWidth - 8) left = x - r.width - 14;
        if (top < 8) top = y + 16;
        tip.style.left = left + 'px'; tip.style.top = top + 'px';
    }
    const hideTip = () => tip.classList.remove('on');

    // Infobulles des barres (attribut data-tip : « titre|ligne|ligne »)
    document.addEventListener('mousemove', (e) => {
        const t = e.target.closest('[data-tip]');
        if (!t) { if (!e.target.closest('#vs-chart')) hideTip(); return; }
        showTip(t.dataset.tip.split('|'), e.clientX, e.clientY);
    });

    // Courbe d'évolution
    const box = document.getElementById('vs-chart');
    const NS = 'http://www.w3.org/2000/svg';
    const C1 = getComputedStyle(document.documentElement).getPropertyValue('--s1').trim() || '#3d4a9e';
    const C2 = getComputedStyle(document.documentElement).getPropertyValue('--s2').trim() || '#b8893a';
    const mk = (tag, attrs, parent) => { const el = document.createElementNS(NS, tag); for (const k in attrs) el.setAttribute(k, attrs[k]); parent && parent.appendChild(el); return el; };

    function niceMax(v) { if (v <= 4) return 4; const p = Math.pow(10, Math.floor(Math.log10(v))); for (const m of [1, 2, 2.5, 5, 10]) if (m * p >= v) return m * p; return 10 * p; }

    function draw() {
        box.textContent = '';
        const W = box.clientWidth, H = box.clientHeight;
        const m = { t: 12, r: 92, b: 26, l: 40 };
        const w = W - m.l - m.r, h = H - m.t - m.b;
        const svg = mk('svg', { viewBox: `0 0 ${W} ${H}` }, box);
        const max = niceMax(Math.max(1, ...S.map((d) => Math.max(d.pages, d.visiteurs))));
        const n = S.length;
        const x = (i) => m.l + (n === 1 ? w / 2 : (i / (n - 1)) * w);
        const y = (v) => m.t + h - (v / max) * h;

        for (let k = 0; k <= 4; k++) {
            const v = (max / 4) * k, yy = y(v);
            mk('line', { x1: m.l, x2: m.l + w, y1: yy, y2: yy, stroke: k === 0 ? '#d9cfb8' : '#f0e9da', 'stroke-width': 1 }, svg);
            mk('text', { x: m.l - 8, y: yy + 4, 'text-anchor': 'end', class: 'vs-axis' }, svg).textContent = nf.format(v);
        }
        const every = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(w / 64))));
        S.forEach((d, i) => { if (i % every === 0 || i === n - 1) mk('text', { x: x(i), y: H - 6, 'text-anchor': 'middle', class: 'vs-axis' }, svg).textContent = d.short; });

        // Aire légère sous les pages vues
        const area = S.map((d, i) => `${x(i)},${y(d.pages)}`).join(' ');
        mk('polygon', { points: `${x(0)},${y(0)} ${area} ${x(n - 1)},${y(0)}`, fill: C1, opacity: 0.07 }, svg);

        [['pages', C1, 'Pages vues'], ['visiteurs', C2, 'Visiteurs']].forEach(([key, color, label]) => {
            mk('polyline', { points: S.map((d, i) => `${x(i)},${y(d[key])}`).join(' '), fill: 'none', stroke: color, 'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }, svg);
            const last = S[n - 1];
            mk('circle', { cx: x(n - 1), cy: y(last[key]), r: 4, fill: color, stroke: '#fff', 'stroke-width': 2 }, svg);
        });
        // Étiquettes directes en fin de courbe (écartées si elles se chevauchent)
        const last = S[n - 1];
        let y1 = y(last.pages), y2 = y(last.visiteurs);
        if (Math.abs(y1 - y2) < 16) { const mid = (y1 + y2) / 2; y1 = Math.min(y1, y2) === y1 ? mid - 8 : mid + 8; y2 = y1 < mid ? mid + 8 : mid - 8; }
        mk('text', { x: x(n - 1) + 10, y: y1 + 4, class: 'vs-endlbl' }, svg).textContent = 'Pages vues';
        mk('text', { x: x(n - 1) + 10, y: y2 + 4, class: 'vs-endlbl' }, svg).textContent = 'Visiteurs';

        // Réticule et infobulle
        const cross = mk('line', { y1: m.t, y2: m.t + h, stroke: '#9aa1b5', 'stroke-width': 1, 'stroke-dasharray': '3 3', opacity: 0 }, svg);
        const dots = [C1, C2].map((c) => mk('circle', { r: 5, fill: c, stroke: '#fff', 'stroke-width': 2, opacity: 0 }, svg));
        const hit = mk('rect', { x: m.l - 6, y: 0, width: w + 12, height: H, fill: 'transparent' }, svg);
        hit.addEventListener('mousemove', (e) => {
            const r = svg.getBoundingClientRect();
            const px = (e.clientX - r.left) * (W / r.width);
            const i = Math.max(0, Math.min(n - 1, Math.round(((px - m.l) / w) * (n - 1))));
            const d = S[i];
            cross.setAttribute('x1', x(i)); cross.setAttribute('x2', x(i)); cross.setAttribute('opacity', 1);
            dots[0].setAttribute('cx', x(i)); dots[0].setAttribute('cy', y(d.pages)); dots[0].setAttribute('opacity', 1);
            dots[1].setAttribute('cx', x(i)); dots[1].setAttribute('cy', y(d.visiteurs)); dots[1].setAttribute('opacity', 1);
            showTip([d.label, `${nf.format(d.pages)} pages vues`, `${nf.format(d.visiteurs)} visiteurs uniques`, `${nf.format(d.verifications)} vérifications`], e.clientX, e.clientY, [null, C1, C2, null]);
        });
        hit.addEventListener('mouseleave', () => { cross.setAttribute('opacity', 0); dots.forEach((d) => d.setAttribute('opacity', 0)); hideTip(); });
    }

    draw();
    let rt; window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(draw, 120); });

    const btn = document.getElementById('vs-toggle'), table = document.getElementById('vs-table');
    btn.addEventListener('click', () => {
        const on = table.classList.toggle('on');
        btn.textContent = on ? 'Masquer le tableau' : 'Voir le tableau';
        btn.setAttribute('aria-expanded', on);
    });
})();
</script>
@endpush
