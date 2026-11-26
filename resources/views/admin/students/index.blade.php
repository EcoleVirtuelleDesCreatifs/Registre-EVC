@extends('layouts.admin')
@section('title', 'Étudiants par document')

@push('head')
<style>
    .sbd-types{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:18px 0}
    .sbd-type{position:relative;display:block;background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px 18px 14px;text-decoration:none;color:var(--ink);transition:transform .15s,box-shadow .15s,border-color .15s;overflow:hidden}
    .sbd-type:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(28,37,87,.08);border-color:var(--gold-l)}
    .sbd-type .lbl{font-size:13px;color:var(--muted);line-height:1.3;min-height:34px}
    .sbd-type b{display:block;font-family:"Libre Baskerville",serif;font-weight:400;font-size:30px;color:var(--navy);line-height:1.1;margin-top:6px}
    .sbd-type small{font-size:12.5px;color:var(--muted)}
    .sbd-type:after{content:"";position:absolute;left:0;right:0;bottom:0;height:3px;background:transparent}
    .sbd-type.on{background:var(--navy);border-color:var(--navy);color:#fff}
    .sbd-type.on .lbl,.sbd-type.on small{color:var(--gold-l)}
    .sbd-type.on b{color:#fff}
    .sbd-type.on:after{background:var(--gold)}
    .sbd-type.empty{opacity:.6}

    .sbd-filters{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:14px}
    .sbd-filters label{display:flex;flex-direction:column;gap:4px;font-size:12.5px;color:var(--muted)}
    .sbd-filters select,.sbd-tools input,.sbd-tools select{font:inherit;font-size:14px;padding:9px 12px;border:1.5px solid var(--line);border-radius:8px;background:#fff;color:var(--navy)}

    .sbd-sum{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:14px}
    .sbd-sum div{background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 14px;font-size:12.5px;color:var(--muted)}
    .sbd-sum b{display:block;font-family:"Libre Baskerville",serif;font-size:22px;color:var(--navy);font-weight:400}

    .sbd-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
    .sbd-chip{font:inherit;font-size:13px;padding:6px 12px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--ink);cursor:pointer}
    .sbd-chip b{color:var(--navy);margin-left:4px}
    .sbd-chip.on{background:var(--gold);border-color:var(--gold);color:var(--navy)}

    .sbd-tools{position:sticky;top:0;z-index:5;display:flex;gap:10px;flex-wrap:wrap;align-items:center;background:var(--ivory);padding:10px 0;margin-bottom:6px;border-bottom:1px solid var(--line)}
    .sbd-tools input[type=search]{flex:1;min-width:220px}
    .sbd-seg{display:inline-flex;border:1.5px solid var(--line);border-radius:8px;overflow:hidden;background:#fff}
    .sbd-seg button{font:inherit;font-size:13.5px;border:0;background:transparent;padding:8px 12px;color:var(--navy);cursor:pointer}
    .sbd-seg button.on{background:var(--navy);color:#fff}
    .sbd-count{font-size:13px;color:var(--muted);margin-left:auto}

    .sbd-group{margin-top:16px}
    .sbd-gh{display:flex;align-items:center;gap:10px;width:100%;font:inherit;background:none;border:0;padding:6px 2px;cursor:pointer;color:var(--navy);text-align:left}
    .sbd-gh h2{font-family:"Libre Baskerville",serif;font-weight:400;font-size:18px;margin:0}
    .sbd-gh .n{background:var(--gold-l);color:var(--navy);font-size:12px;font-weight:700;border-radius:999px;padding:2px 9px}
    .sbd-gh .line{flex:1;height:1px;background:var(--line)}
    .sbd-gh .chev{transition:transform .2s;color:var(--muted)}
    .sbd-group.closed .chev{transform:rotate(-90deg)}
    .sbd-group.closed .sbd-body{display:none}

    .sbd-body .card{padding:0;overflow-x:auto}
    .sbd-who{display:flex;align-items:center;gap:10px}
    .sbd-av{flex:none;width:34px;height:34px;border-radius:50%;display:grid;place-items:center;font-size:12.5px;font-weight:700;letter-spacing:.5px;color:#fff;background:var(--navy)}
    .sbd-who small{display:block;color:var(--muted);font-size:12px}
    .sbd-num{font-variant-numeric:tabular-nums;white-space:nowrap}

    .sbd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
    .sbd-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:10px;transition:box-shadow .15s,border-color .15s}
    .sbd-card:hover{box-shadow:0 8px 22px rgba(28,37,87,.08);border-color:var(--gold-l)}
    .sbd-card .sbd-av{width:44px;height:44px;font-size:15px}
    .sbd-card .nm{font-weight:600;color:var(--navy);line-height:1.25}
    .sbd-card dl{display:grid;grid-template-columns:auto 1fr;gap:3px 10px;margin:0;font-size:12.5px}
    .sbd-card dt{color:var(--muted)}
    .sbd-card dd{margin:0;color:var(--ink)}
    .sbd-card .foot{display:flex;justify-content:space-between;align-items:center;gap:8px;border-top:1px solid #f0e9da;padding-top:10px;margin-top:auto}
    .sbd-card .foot a{font-size:13px}
    .sbd-empty{padding:40px 20px;text-align:center;color:var(--muted);background:#fff;border:1px dashed var(--line);border-radius:14px;margin-top:16px}
    mark{background:var(--gold-l);color:inherit;border-radius:3px;padding:0 1px}

    .sbd-act{white-space:nowrap;text-align:right}
    .sbd-del{font:inherit;font-size:12.5px;font-weight:600;padding:6px 10px;border-radius:8px;border:1px solid #f0c9c5;background:#fff;color:var(--bad);cursor:pointer}
    .sbd-del:hover{background:var(--bad);color:#fff;border-color:var(--bad)}
    .sbd-lock{font-size:12px;color:var(--muted);text-decoration:none;border:1px dashed var(--line);border-radius:8px;padding:5px 9px}
    .sbd-lock:hover{color:var(--navy);border-color:var(--gold)}
    .sbd-ck{width:16px;height:16px;accent-color:var(--navy);cursor:pointer}
    tr.sel td{background:#fdf6e8}
    .sbd-card.sel{border-color:var(--gold);box-shadow:0 0 0 2px var(--gold-l)}
    .sbd-bulk{display:none;align-items:center;gap:10px;background:var(--navy);color:#fff;border-radius:10px;padding:8px 10px 8px 14px;font-size:13.5px}
    .sbd-bulk.on{display:inline-flex}
    .sbd-bulk button{font:inherit;font-size:13px;font-weight:600;border:0;border-radius:8px;padding:6px 12px;cursor:pointer}
    .sbd-bulk .go{background:#fff;color:var(--bad)}
    .sbd-bulk .no{background:transparent;color:var(--gold-l)}
    .sbd-print{display:none}
    @media (max-width:760px){.sbd-count{margin-left:0;width:100%}}
    @media print{
        @page{size:A4;margin:14mm}
        body{background:#fff}
        .top,.side,.sbd-types,.sbd-filters,.sbd-chips,.sbd-tools,.sbd-noprint,.sbd-act,.sbd-ckc,.alert{display:none!important}
        .adm{display:block}.adm main{padding:0;max-width:none}
        .sbd-print{display:block;border-bottom:2px solid var(--navy);padding-bottom:8px;margin-bottom:10px}
        .sbd-print .t{font-family:"Libre Baskerville",serif;font-size:18px;color:var(--navy)}
        .sbd-print .s{font-size:11px;color:var(--muted)}
        .sbd-sum{grid-template-columns:repeat(5,1fr)}
        .sbd-sum div{padding:6px 10px}.sbd-sum b{font-size:16px}
        .sbd-group{break-inside:auto}.sbd-group.closed .sbd-body{display:block}
        .sbd-gh .chev{display:none}
        table{font-size:11px}th,td{padding:5px 6px!important}
        tr{break-inside:avoid}
        .sbd-av{display:none}
        a{text-decoration:none;color:inherit}
    }
</style>
@endpush

@section('admin')
    @php
        $q = array_filter($filters);
        $statusOptions = ['delivres' => 'Délivrés (publiés et révoqués)', 'en_cours' => 'En cours (avant délivrance)'] + config('evc.statuses');
    @endphp

    <div class="sbd-print">
        <div class="t">École Virtuelle des Créatifs — Liste des étudiants</div>
        <div class="s">
            {{ $type?->label }}
            @if ($filters['formation']) · {{ config('evc.formations.'.$filters['formation']) }} @endif
            @if ($filters['promotion']) · Promotion {{ $filters['promotion'] }} @endif
            @if ($filters['status']) · {{ $statusOptions[$filters['status']] }} @endif
            · Imprimé le {{ now()->format('d/m/Y à H:i') }} par {{ auth()->user()->name }}
        </div>
    </div>

    <div class="row sbd-noprint" style="justify-content:space-between">
        <div>
            <h1>Étudiants par document</h1>
            <p class="lead" style="margin:0">Choisissez un type de document pour voir les étudiants concernés, puis regroupez, triez ou recherchez.</p>
        </div>
        @if ($type)
            <div class="row">
                <button type="button" class="btn ghost" onclick="window.print()">Imprimer la liste</button>
                <a class="btn ghost" href="{{ route('admin.par-document.export', $q + ['type' => $type->code]) }}">Exporter (CSV)</a>
            </div>
        @endif
    </div>

    {{-- Types de document --}}
    <nav class="sbd-types" aria-label="Types de document">
        @foreach ($types as $t)
            @php $c = $counts[$t->id] ?? null; @endphp
            <a href="{{ route('admin.par-document.index', $q + ['type' => $t->code]) }}"
               class="sbd-type {{ $type?->id === $t->id ? 'on' : '' }} {{ $c ? '' : 'empty' }}"
               @if ($type?->id === $t->id) aria-current="page" @endif>
                <span class="lbl">{{ $t->label }}</span>
                <b>{{ $c->students ?? 0 }}</b>
                <small>étudiant{{ ($c->students ?? 0) > 1 ? 's' : '' }} · {{ $c->docs ?? 0 }} document{{ ($c->docs ?? 0) > 1 ? 's' : '' }}</small>
            </a>
        @endforeach
    </nav>

    {{-- Filtres serveur --}}
    <form method="GET" class="sbd-filters" id="sbd-filters">
        <input type="hidden" name="type" value="{{ $type?->code }}">
        <label>Formation
            <select name="formation"><option value="">Toutes</option>
                @foreach (config('evc.formations') as $code => $label)<option value="{{ $code }}" @selected($filters['formation'] === $code)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <label>Promotion
            <select name="promotion"><option value="">Toutes</option>
                @foreach ($promotions as $p)<option @selected($filters['promotion'] === $p)>{{ $p }}</option>@endforeach
            </select>
        </label>
        <label>Statut
            <select name="status"><option value="">Tous</option>
                @foreach ($statusOptions as $code => $label)<option value="{{ $code }}" @selected($filters['status'] === $code)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <noscript><button class="btn" type="submit">Filtrer</button></noscript>
        @if ($q)<a class="btn ghost" href="{{ route('admin.par-document.index', ['type' => $type?->code]) }}">Réinitialiser</a>@endif
    </form>

    @if ($type)
        <div class="sbd-sum">
            <div><b>{{ $summary['students'] }}</b>Étudiants distincts</div>
            <div><b>{{ $summary['documents'] }}</b>Documents</div>
            <div><b style="color:var(--ok)">{{ $summary['publies'] }}</b>Publiés</div>
            <div><b style="{{ $summary['en_cours'] ? 'color:var(--warn)' : '' }}">{{ $summary['en_cours'] }}</b>En cours</div>
            <div><b style="{{ $summary['revoques'] ? 'color:var(--bad)' : '' }}">{{ $summary['revoques'] }}</b>Révoqués</div>
        </div>

        @if ($byFormation->filter(fn ($n, $k) => $k !== '')->count() > 1)
            <div class="sbd-chips" id="sbd-chips" role="group" aria-label="Filtrer par formation">
                <button type="button" class="sbd-chip on" data-f="">Toutes <b>{{ $summary['documents'] }}</b></button>
                @foreach ($byFormation as $code => $n)
                    <button type="button" class="sbd-chip" data-f="{{ $code }}">{{ $code === '' ? 'Sans formation' : config('evc.formations.'.$code, $code) }} <b>{{ $n }}</b></button>
                @endforeach
            </div>
        @endif

        @if ($truncated)
            <div class="alert err">Affichage limité aux {{ $limit }} premiers documents : affinez avec les filtres ou utilisez l’export CSV pour la liste complète.</div>
        @endif

        <div class="sbd-tools">
            <input type="search" id="sbd-q" placeholder="Rechercher un nom, un matricule, une référence…" aria-label="Rechercher">
            <select id="sbd-group" aria-label="Regrouper par">
                <option value="promotion">Regrouper par promotion</option>
                <option value="formation">Regrouper par formation</option>
                <option value="status">Regrouper par statut</option>
                <option value="year">Regrouper par année de délivrance</option>
                <option value="letter">Regrouper par initiale</option>
                <option value="none">Sans regroupement</option>
            </select>
            <select id="sbd-sort" aria-label="Trier par">
                <option value="name">Nom A → Z</option>
                <option value="-name">Nom Z → A</option>
                <option value="-issued">Délivrance récente</option>
                <option value="issued">Délivrance ancienne</option>
                <option value="reference">Référence</option>
            </select>
            <div class="sbd-seg" role="group" aria-label="Affichage">
                <button type="button" data-view="table" title="Affichage en liste">☰ Liste</button>
                <button type="button" data-view="cards" title="Affichage en cartes">▦ Cartes</button>
            </div>
            <span class="sbd-bulk" id="sbd-bulk" aria-live="polite">
                <span id="sbd-bulk-n"></span>
                <button type="button" class="go" id="sbd-bulk-go">Supprimer la sélection</button>
                <button type="button" class="no" id="sbd-bulk-no">Annuler</button>
            </span>
            <span class="sbd-count" id="sbd-count" aria-live="polite"></span>
        </div>
        <form method="POST" action="{{ route('admin.par-document.destroy') }}" id="sbd-del-form" hidden>@csrf @method('DELETE')</form>

        <div id="sbd-results"></div>
        <noscript>
            <div class="sbd-empty">Activez JavaScript pour afficher la liste, ou utilisez l’export CSV.</div>
        </noscript>
    @else
        <div class="sbd-empty">Aucun type de document n’est configuré.</div>
    @endif
@endsection

@if ($type)
@push('scripts')
<script>
(function () {
    const DATA = @json($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    const STATUSES = @json(config('evc.statuses'));
    const FORMATIONS = @json(config('evc.formations'));
    const KEY = 'evc.parDocument';
    const CAN_DELETE = DATA.some((r) => r.can_delete);
    const selected = new Set();

    const $ = (id) => document.getElementById(id);
    const els = { q: $('sbd-q'), group: $('sbd-group'), sort: $('sbd-sort'), count: $('sbd-count'), out: $('sbd-results') };
    const segBtns = document.querySelectorAll('.sbd-seg button');

    let saved = {};
    try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { saved = {}; }
    const state = {
        q: '',
        formation: '',
        group: saved.group || 'promotion',
        sort: saved.sort || 'name',
        view: saved.view || 'table',
        closed: new Set(),
    };
    els.group.value = state.group;
    els.sort.value = state.sort;
    const save = () => { try { localStorage.setItem(KEY, JSON.stringify({ group: state.group, sort: state.sort, view: state.view })); } catch (e) {} };

    // Filtres serveur : rechargement immédiat au changement
    document.querySelectorAll('#sbd-filters select').forEach((s) => s.addEventListener('change', () => s.form.submit()));

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fold = (s) => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const hl = (text) => {
        const t = String(text ?? '');
        if (!state.q) return esc(t);
        const f = fold(t), i = f.indexOf(state.q);
        if (i < 0) return esc(t);
        return esc(t.slice(0, i)) + '<mark>' + esc(t.slice(i, i + state.q.length)) + '</mark>' + esc(t.slice(i + state.q.length));
    };
    const initials = (n) => String(n).trim().split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase() || '?';
    const COLORS = ['#1c2557', '#283378', '#b8893a', '#3d4a8f', '#8a6526', '#4a5370'];
    const color = (n) => { let h = 0; for (const c of String(n)) h = (h * 31 + c.charCodeAt(0)) >>> 0; return COLORS[h % COLORS.length]; };
    const pill = (r) => `<span class="pill ${esc(r.status)}">${esc(r.status_label)}</span>`;
    const ref = (r) => r.reference ? hl(r.reference) : '<span class="muted">non attribuée</span>';

    DATA.forEach((r) => { r._search = fold([r.name, r.matricule, r.email, r.reference, r.promotion].join(' ')); });

    const groupKey = {
        promotion: (r) => r.promotion || 'Sans promotion',
        formation: (r) => r.formation_label || 'Sans formation',
        status: (r) => r.status_label,
        year: (r) => r.issued ? r.issued.slice(0, 4) : 'Non délivré',
        letter: (r) => (fold(r.name).replace(/[^a-z]/g, '')[0] || '#').toUpperCase(),
        none: () => '',
    };
    const statusOrder = Object.keys(STATUSES);

    function sorter(key) {
        const desc = key.startsWith('-'), k = key.replace('-', '');
        return (a, b) => {
            let x, y;
            if (k === 'name') { x = fold(a.name); y = fold(b.name); }
            else if (k === 'issued') { x = a.issued || ''; y = b.issued || ''; }
            else { x = a.reference || '~'; y = b.reference || '~'; }
            const c = x < y ? -1 : x > y ? 1 : 0;
            return (desc ? -c : c) || fold(a.name).localeCompare(fold(b.name), 'fr');
        };
    }

    function groupSort(a, b) {
        if (state.group === 'status') return statusOrder.indexOf(DATA.find((r) => r.status_label === a)?.status) - statusOrder.indexOf(DATA.find((r) => r.status_label === b)?.status);
        const last = /^(Sans|Non)/;
        if (last.test(a) !== last.test(b)) return last.test(a) ? 1 : -1;
        if (state.group === 'year' || state.group === 'promotion') return b.localeCompare(a, 'fr', { numeric: true });
        return a.localeCompare(b, 'fr', { numeric: true });
    }

    const actionHtml = (r) => r.can_delete
        ? `<button type="button" class="sbd-del" data-del="${r.id}" data-name="${esc(r.name)}" title="Supprimer ce dossier (brouillon)">🗑 Supprimer</button>`
        : `<a class="sbd-lock" href="${esc(r.url)}" title="${r.reference ? 'Document délivré : il ne peut pas être supprimé. Ouvrez le dossier pour le révoquer.' : 'Dossier en cours de validation : ouvrez-le pour le traiter.'}">🔒 ${r.reference ? 'Révoquer…' : 'Ouvrir'}</a>`;
    const ckHtml = (r) => r.can_delete ? `<input type="checkbox" class="sbd-ck" data-ck="${r.id}" ${selected.has(r.id) ? 'checked' : ''} aria-label="Sélectionner ${esc(r.name)}">` : '';

    function tableHtml(rows) {
        const deletable = rows.filter((r) => r.can_delete);
        const all = deletable.length && deletable.every((r) => selected.has(r.id));
        return `<div class="card"><table>
            <thead><tr>${CAN_DELETE ? `<th class="sbd-ckc" style="width:34px">${deletable.length ? `<input type="checkbox" class="sbd-ck" data-ckall="${deletable.map((r) => r.id).join(',')}" ${all ? 'checked' : ''} aria-label="Tout sélectionner">` : ''}</th>` : ''}<th>Étudiant</th><th>Promotion</th><th>Formation</th><th>Référence</th><th>Délivré le</th><th>Statut</th><th class="sbd-noprint">PDF</th><th class="sbd-act"></th></tr></thead>
            <tbody>${rows.map((r) => `<tr class="${selected.has(r.id) ? 'sel' : ''}">
                ${CAN_DELETE ? `<td class="sbd-ckc">${ckHtml(r)}</td>` : ''}
                <td><div class="sbd-who"><span class="sbd-av" style="background:${color(r.name)}">${esc(initials(r.name))}</span>
                    <div>${r.holder_url ? `<a href="${esc(r.holder_url)}"><strong>${hl(r.name)}</strong></a>` : `<strong>${hl(r.name)}</strong>`}
                    ${r.matricule || r.email ? `<small>${hl(r.matricule || '')}${r.matricule && r.email ? ' · ' : ''}${hl(r.email || '')}</small>` : ''}</div></div></td>
                <td>${hl(r.promotion || '—')}</td>
                <td>${esc(r.formation_label || '—')}</td>
                <td class="sbd-num"><a href="${esc(r.url)}">${ref(r)}</a>${r.legacy ? ' <span class="tag">historique</span>' : ''}</td>
                <td class="sbd-num">${esc(r.issued_fr || '—')}</td>
                <td>${pill(r)}</td>
                <td class="sbd-noprint">${r.pdf ? '<span class="fileinfo">✓</span>' : '<span class="fileinfo none">manquant</span>'}</td>
                <td class="sbd-act">${actionHtml(r)}</td>
            </tr>`).join('')}</tbody></table></div>`;
    }

    function cardsHtml(rows) {
        return `<div class="sbd-grid">${rows.map((r) => `<article class="sbd-card ${selected.has(r.id) ? 'sel' : ''}">
            <div class="sbd-who">${CAN_DELETE ? `<span class="sbd-ckc">${ckHtml(r)}</span>` : ''}<span class="sbd-av" style="background:${color(r.name)}">${esc(initials(r.name))}</span>
                <div><div class="nm">${hl(r.name)}</div>${r.matricule ? `<small>${hl(r.matricule)}</small>` : ''}</div></div>
            <dl>
                <dt>Référence</dt><dd class="sbd-num">${ref(r)}</dd>
                <dt>Promotion</dt><dd>${hl(r.promotion || '—')}</dd>
                ${r.formation_label ? `<dt>Formation</dt><dd>${esc(r.formation_label)}</dd>` : ''}
                <dt>Délivré le</dt><dd>${esc(r.issued_fr || '—')}</dd>
            </dl>
            <div class="foot">${pill(r)}<span><a href="${esc(r.url)}">Dossier</a>${r.holder_url ? ` · <a href="${esc(r.holder_url)}">Fiche</a>` : ''}</span></div>
            ${r.can_delete ? `<div class="sbd-act" style="text-align:left">${actionHtml(r)}</div>` : ''}
        </article>`).join('')}</div>`;
    }

    function render() {
        const rows = DATA
            .filter((r) => !state.formation || (r.formation || '') === state.formation)
            .filter((r) => !state.q || r._search.includes(state.q))
            .sort(sorter(state.sort));

        const students = new Set(rows.map((r) => r.holder_id)).size;
        els.count.textContent = `${students} étudiant${students > 1 ? 's' : ''} · ${rows.length} / ${DATA.length} document${DATA.length > 1 ? 's' : ''}`;
        segBtns.forEach((b) => b.classList.toggle('on', b.dataset.view === state.view));
        updateBulk();

        if (!rows.length) {
            els.out.innerHTML = `<div class="sbd-empty">${DATA.length ? 'Aucun étudiant ne correspond à cette recherche.' : 'Aucun document de ce type pour ces critères.'}</div>`;
            return;
        }

        const groups = new Map();
        rows.forEach((r) => { const k = groupKey[state.group](r); if (!groups.has(k)) groups.set(k, []); groups.get(k).push(r); });
        const body = state.view === 'cards' ? cardsHtml : tableHtml;

        if (state.group === 'none') { els.out.innerHTML = `<div class="sbd-group">${body(rows)}</div>`; return; }

        els.out.innerHTML = [...groups.keys()].sort(groupSort).map((k) => {
            const list = groups.get(k);
            const n = new Set(list.map((r) => r.holder_id)).size;
            const closed = state.closed.has(state.group + ':' + k);
            return `<section class="sbd-group ${closed ? 'closed' : ''}" data-k="${esc(state.group + ':' + k)}">
                <button type="button" class="sbd-gh" aria-expanded="${!closed}">
                    <span class="chev">▾</span><h2>${state.group === 'promotion' && k !== 'Sans promotion' ? 'Promotion ' : ''}${esc(k)}</h2>
                    <span class="n">${n} étudiant${n > 1 ? 's' : ''}</span><span class="line"></span>
                </button>
                <div class="sbd-body">${body(list)}</div>
            </section>`;
        }).join('');
    }

    function updateBulk() {
        const bar = $('sbd-bulk');
        bar.classList.toggle('on', selected.size > 0);
        $('sbd-bulk-n').textContent = `${selected.size} dossier${selected.size > 1 ? 's' : ''} sélectionné${selected.size > 1 ? 's' : ''}`;
    }

    function submitDelete(ids, message) {
        if (!ids.length || !window.confirm(message)) return;
        const f = $('sbd-del-form');
        f.querySelectorAll('input[name="ids[]"]').forEach((i) => i.remove());
        ids.forEach((id) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = id; f.appendChild(i); });
        f.submit();
    }

    els.out.addEventListener('change', (e) => {
        const c = e.target;
        if (c.dataset.ckall !== undefined) {
            c.dataset.ckall.split(',').map(Number).forEach((id) => c.checked ? selected.add(id) : selected.delete(id));
            render();
        } else if (c.dataset.ck !== undefined) {
            const id = Number(c.dataset.ck);
            c.checked ? selected.add(id) : selected.delete(id);
            render();
        }
    });

    $('sbd-bulk-no').addEventListener('click', () => { selected.clear(); render(); });
    $('sbd-bulk-go').addEventListener('click', () => submitDelete([...selected],
        `Supprimer ${selected.size} dossier${selected.size > 1 ? 's' : ''} ?\n\nSeuls les brouillons sans référence sont supprimés. L’opération est enregistrée dans l’historique.`));

    els.out.addEventListener('click', (e) => {
        const del = e.target.closest('[data-del]');
        if (del) { submitDelete([Number(del.dataset.del)], `Supprimer le dossier de ${del.dataset.name} ?\n\nCe brouillon disparaîtra de la liste. L’opération est enregistrée dans l’historique.`); return; }
        const h = e.target.closest('.sbd-gh');
        if (!h) return;
        const sec = h.parentElement, k = sec.dataset.k;
        state.closed.has(k) ? state.closed.delete(k) : state.closed.add(k);
        sec.classList.toggle('closed');
        h.setAttribute('aria-expanded', !sec.classList.contains('closed'));
    });

    let t;
    els.q.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => { state.q = fold(els.q.value.trim()); render(); }, 120); });
    els.group.addEventListener('change', () => { state.group = els.group.value; save(); render(); });
    els.sort.addEventListener('change', () => { state.sort = els.sort.value; save(); render(); });
    segBtns.forEach((b) => b.addEventListener('click', () => { state.view = b.dataset.view; save(); render(); }));
    document.querySelectorAll('#sbd-chips .sbd-chip').forEach((c) => c.addEventListener('click', () => {
        state.formation = c.dataset.f;
        document.querySelectorAll('#sbd-chips .sbd-chip').forEach((x) => x.classList.toggle('on', x === c));
        render();
    }));

    // À l'impression : liste complète en tableau, groupes dépliés
    let before = null;
    window.addEventListener('beforeprint', () => { if (state.view !== 'table') { before = state.view; state.view = 'table'; render(); } });
    window.addEventListener('afterprint', () => { if (before) { state.view = before; before = null; render(); } });

    render();
})();
</script>
@endpush
@endif
