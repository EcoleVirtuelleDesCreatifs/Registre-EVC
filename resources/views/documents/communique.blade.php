@use('App\Support\FrenchText', 'F')
@php
    $d = $c['details'];
    $s = F::signatory($d['signataire'] ?? 'dg');
    // Contenu riche (éditeur) → lignes de travail ; seules les balises de style sont conservées.
    $raw = trim($d['contenu'] ?? '');
    if (str_contains($raw, '<')) {
        $raw = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', "\n« \$1 »\n", $raw);
        $raw = preg_replace('/<li[^>]*>/i', "\n@@NUM@@", $raw);
        $raw = preg_replace('/<br\s*\/?>/i', "\n", $raw);
        $raw = preg_replace('/<\/(p|div|ol|ul|li|h[1-6])>/i', "\n", $raw);
        $raw = preg_replace('/<(\/?)(b|i|u|strong|em|s|span)(\s[^>]*)?>/i', '§§$1$2$3§§', $raw);
        $raw = preg_replace('/<[^>]+>/', '', $raw);
        $raw = preg_replace('/§§(\/?)(b|i|u|strong|em|s|span)(\s[^§]*)?§§/', '<$1$2$3>', $raw);
        $raw = html_entity_decode(str_replace('&nbsp;', ' ', $raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $dispositions = [];
    $citation = '';
    $corps = [];
    $lead = '';
    $section = 'intro';
    foreach (preg_split('/\R/', $raw) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (str_starts_with($line, '@@NUM@@')) { $line = trim(substr($line, 7)); $isList = true; } else { $isList = false; }
        if ($isList || preg_match('/^(?:<[^>]+>\s*)*\d{1,2}[.\)]\s*/', $line)) {
            $dispositions[] = ['t' => $isList ? $line : preg_replace('/^((?:<[^>]+>\s*)*)\d{1,2}[.\)]\s*/', '$1', $line), 'd' => ''];
            $section = 'dispo';
        } elseif (preg_match('/^(?:<[^>]+>\s*)*«/', $line)) {
            $section = 'citation';
            $citation .= ($citation ? ' ' : '').$line;
        } elseif ($section === 'citation' && str_starts_with($line, '—')) {
            $section = 'fin';
        } elseif ($section === 'citation' && ! preg_match('/»\s*(?:<[^>]+>\s*)*$/', $citation)) {
            $citation .= ' '.$line;
        } elseif ($section === 'dispo') {
            $dispositions[array_key_last($dispositions)]['d'] .= ($dispositions[array_key_last($dispositions)]['d'] ? ' ' : '').$line;
        } elseif ($section === 'intro' && $lead === '') {
            $lead = $line;
        } else {
            $corps[] = $line;
        }
    }
    // Retire le préfixe « Abidjan … — L'École Virtuelle des Créatifs » s'il est recopié dans le texte.
    $lead = trim(preg_replace('/^a\s*bidjan.*[—–-]\s*l[\'’]école virtuelle des créatifs\s*/iu', '', $lead));
    // La formule de clôture est déjà imprimée : on ignore sa recopie.
    $corps = array_values(array_filter($corps, fn ($l) => ! preg_match('/^l[\'’]administration reste à (la|leur) disposition/iu', strip_tags($l))));
    $citation = trim(str_replace('«', '', preg_replace('/»(?=\s*(?:<[^>]+>\s*)*$)/', '', $citation)));
    // Mise à l'échelle automatique si le contenu est long (évite tout chevauchement).
    $chars = mb_strlen(strip_tags($lead.implode(' ', $corps).$citation.implode(' ', array_map(fn ($x) => $x['t'].' '.$x['d'], $dispositions))));
    $fit = $chars > 1050 ? 'cm-xs' : ($chars > 650 ? 'cm-md' : ($chars > 350 ? 'cm-sm' : ''));
    // Hauteur estimée de l'encadré Référence/Objet/Destinataires (~28 car./ligne/cellule) + marge avant le contenu.
    $metaLines = max(1, (int) ceil(max(
        mb_strlen($c['reference'] ?: 'attribuée à la délivrance') / 28,
        mb_strlen($d['objet'] ?? '') / 28,
        mb_strlen($d['destinataires'] ?? '') / 28
    )));
    $contentTop = 36 + 9 + $metaLines * 4 + 5;
    $refSlash = $c['reference'] ? str_replace('-', '/', $c['reference']) : 'attribuée à la délivrance';
@endphp

{{-- ===== Page unique ===== --}}
<div class="dp p">
    <div class="cm-bar"><div class="orange">Édition<br>{{ $c['year'] }}</div><div class="side"><img src="{{ $asset('cm-side.png') }}" alt=""></div></div>
    <div class="cm-head">
        <img class="lgo" src="{{ $asset('logo-evc.png') }}" alt="EVC">
        <div class="right">
            <div class="tag">[ Communiqué / Avis / Information ]</div>
            <div class="dir">Direction Générale</div>
            <div class="loc">Abidjan, {{ $c['issued'] ? 'le '.F::date($c['issued']) : 'le … (date de délivrance)' }}</div>
        </div>
    </div>
    <div class="cm-rule"></div>
    <div class="cm-wm"><i class="w1"></i><i class="w2"></i><i class="w3"></i><i class="w4"></i></div>

    <div class="cm-body{{ $fit ? ' '.$fit : '' }}">
        <div class="cm-badge">Communiqué<span class="n">N°<br>{{ $refSlash }}</span><span class="o">Officiel</span></div>
        <div class="cm-titlebox">
            <div class="cm-kicker">Communiqué officiel <span>· Official Statement</span></div>
            <div class="cm-title">{{ $d['objet'] ?? '… (objet du communiqué)' }}</div>
        </div>

        <div class="cm-meta"><table><tr>
            <td style="width:34%"><span class="k">Référence</span>{{ $refSlash }}</td>
            <td style="width:33%"><span class="k">Objet</span>{{ $d['objet'] ?? '…' }}</td>
            <td><span class="k">Destinataires</span>{{ $d['destinataires'] ?? 'Public concerné' }}</td>
        </tr></table></div>

        <div class="cm-content" style="top:{{ $contentTop }}mm">
            @if ($lead)
                <div class="cm-lead"><b>Abidjan (Côte d'Ivoire), {{ $c['issued'] ? mb_strtoupper(F::date($c['issued'])) : '[JJ mois AAAA]' }}</b> — L'École Virtuelle des Créatifs {!! $lead !!}</div>
            @endif
            @foreach ($corps as $p)
                <div class="cm-lead" style="margin-top:3mm">{!! $p !!}</div>
            @endforeach

            @if ($dispositions)
                <div class="cm-dispo"><table>
                    @foreach ($dispositions as $i => $dispo)
                        <tr><td class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td><td><b>{!! $dispo['t'] !!}</b>@if ($dispo['d'])<br>{!! $dispo['d'] !!}@endif</td></tr>
                    @endforeach
                </table></div>
            @endif

            @if ($citation)
                <div class="cm-quote"><span class="qm">«</span>{!! $citation !!} »
                    <span class="who">— {{ $s['title'] === 'Directeur Général' ? 'Le' : 'La' }} <b>{{ $s['title'] }}</b> de l'École Virtuelle des Créatifs</span>
                </div>
            @endif

            <div class="cm-closing">L'administration reste à la disposition des apprenants pour toute question.</div>
            <div class="cm-end">· · ·</div>
        </div>

        <div class="cm-apropos">
            <div class="t">À propos de l'École Virtuelle des Créatifs</div>
            <p>L'École Virtuelle des Créatifs (EVC) est la première école digitale d'Afrique francophone. Elle forme en ligne et en présentiel aux métiers du numérique : Design graphic et infographie, Community Management &amp; Social Media Management, Gestion informatique et Appliquée.</p>
            <div class="cm-auth">
                <div class="t">Apprenez autrement</div>
                <div class="c">Authentification {{ $refSlash }}</div>
                @include('documents.partials.qr', ['pos' => 'right:1.5mm;top:1.5mm', 'size' => '10mm', 'caption' => false])
            </div>
        </div>

        <div class="cm-sign">
            <div class="fait">Fait à Abidjan, {{ $c['issued'] ? 'le '.F::date($c['issued']) : 'le … (date de délivrance)' }}</div>
            <div class="t">{{ $s['feminine'] ? 'La' : 'Le' }} {{ $s['title'] }},</div>
            @include('documents.partials.signature', ['signataire' => $d['signataire'] ?? 'dg', 'stamp' => true])
            <div class="who">{{ mb_strtoupper($s['name']) }}</div>
            <div class="role">{{ $s['title'] }} · École Virtuelle des Créatifs</div>
        </div>
    </div>

    <div class="cm-foot"><table><tr>
        <td>
            <span class="titre">Registre · Identification légale</span>
            École Virtuelle des Créatifs (EVC) &nbsp;|&nbsp; SARL au capital de 1 000 000 FCFA &nbsp;|&nbsp; RCCM N° CI-ABJ-03-2024-B12-00430<br>
            Siège : Abidjan &nbsp;·&nbsp; Tél. (+225) 07 47 25 95 07 / 07 19 25 86 02 &nbsp;·&nbsp; direc@ecolevirtuelledescreatifs.com &nbsp;·&nbsp; www.ecolevirtuelledescreatifs.com
        </td>
        <td style="width:52mm;text-align:right">Code d'authentification : <b>{{ $refSlash }}</b><br>Seul fait foi l'original revêtu du cachet, de la signature et de ce code.</td>
    </tr></table></div>
    @include('documents.partials.specimen')
</div>
