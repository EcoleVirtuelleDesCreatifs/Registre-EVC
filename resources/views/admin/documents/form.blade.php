@extends('layouts.admin')
@section('title', $document->exists ? 'Modifier le dossier' : 'Nouveau document')
@section('admin')
    <h1>{{ $document->exists ? 'Modifier le dossier' : 'Nouveau document' }}</h1>
    <p class="lead">Le dossier est créé en <strong>brouillon</strong>. La référence officielle n’est attribuée qu’à la délivrance, après contrôle pédagogique et approbation de la Direction générale.</p>

    <form class="card form" method="POST" action="{{ $document->exists ? route('admin.documents.update', $document) : route('admin.documents.store') }}">
        @csrf
        @if ($document->exists) @method('PUT') @endif

        <div class="full"><h2 style="margin:0">Titulaire</h2></div>
        @if ($document->exists)
            <div class="full">
                <strong>{{ $holder?->full_name }}</strong>
                <a class="small" href="{{ route('admin.beneficiaires.edit', $holder) }}">Modifier la fiche du titulaire</a>
            </div>
        @else
            <label class="full">Titulaire existant
                <input id="holder-search" list="holder-list" placeholder="Tapez un nom ou un matricule…" value="{{ old('holder_label', $holder?->full_name) }}" autocomplete="off" name="holder_label">
                <datalist id="holder-list"></datalist>
                <input type="hidden" name="holder_id" id="holder-id" value="{{ old('holder_id', $holder?->id) }}">
                <span class="fileinfo" id="holder-state">{{ $holder ? 'Titulaire sélectionné : '.$holder->full_name : 'Aucun titulaire sélectionné.' }}</span>
            </label>
            @error('holder_id')<div class="err-text full">{{ $message }}</div>@enderror
            <div class="full notice">Nouveau titulaire ? Laissez le champ ci-dessus vide et renseignez :</div>
            <label>Nom complet <input name="holder_name" value="{{ old('holder_name') }}" placeholder="Prénom(s) NOM"></label>
            <label>E-mail <input type="email" name="holder_email" value="{{ old('holder_email') }}"></label>
            <label>Matricule My EVC <input name="holder_external_id" value="{{ old('holder_external_id') }}"></label>
            @error('holder_name')<div class="err-text full">{{ $message }}</div>@enderror
            @error('holder_external_id')<div class="err-text full">{{ $message }}</div>@enderror
        @endif

        <div class="full"><h2 style="margin:10px 0 0">Document</h2></div>
        <label>Type de document
            <select name="document_type_id" id="type" required>
                @foreach ($types as $t)
                    <option value="{{ $t->id }}" data-formation="{{ $t->requires_formation || $t->is_certificate ? 1 : 0 }}" @selected(old('document_type_id', $document->document_type_id) == $t->id)>{{ $t->label }}</option>
                @endforeach
            </select>
        </label>
        <label>Formation
            <select name="formation" id="formation">
                <option value="">— Aucune —</option>
                @foreach (config('evc.formations') as $code => $label)
                    <option value="{{ $code }}" @selected(old('formation', $document->formation) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        @error('formation')<div class="err-text full">{{ $message }}</div>@enderror
        <label>Promotion <input name="promotion" value="{{ old('promotion', $document->promotion) }}" placeholder="ex. 2026-A"></label>
        <label class="check" style="align-self:end"><input type="hidden" name="pdf_public" value="0"><input type="checkbox" name="pdf_public" value="1" @checked(old('pdf_public', $document->exists ? $document->pdf_public : true))> PDF consultable après vérification publique</label>
        <label class="full">Note interne (jamais publiée) <textarea name="internal_note" rows="3">{{ old('internal_note', $document->internal_note) }}</textarea></label>


        @php $dt = fn ($k, $def = null) => old('details.'.$k, $document->detail($k, $def)); @endphp
        <div class="full"><h2 style="margin:10px 0 0">Contenu du document</h2>
            <p class="fileinfo" style="margin:4px 0 0">Ces informations sont imprimées automatiquement sur le document officiel, avec la référence et le QR code.</p></div>
        <label>Civilité du titulaire
            <select name="details[civilite]"><option value="M" @selected($dt('civilite', 'M') === 'M')>Monsieur</option><option value="Mme" @selected($dt('civilite') === 'Mme')>Madame</option></select>
        </label>
        <label data-for="attestation_formation attestation_stage recommandation communique">Signataire
            <select name="details[signataire]">@foreach (config('evc.signataires') as $k => $s)<option value="{{ $k }}" @selected($dt('signataire', 'dg') === $k)>{{ $s['name'] }} — {{ $s['title'] }}</option>@endforeach</select>
        </label>
        <label data-for="certificat">Mention (facultatif) <input name="details[mention]" value="{{ $dt('mention') }}" placeholder="ex. avec la mention Très bien"></label>

        <label data-for="attestation_formation">Statut de la formation
            <select name="details[statut]">@foreach (config('evc.statuts_formation') as $k => $l)<option value="{{ $k }}" @selected($dt('statut', 'en_cours') === $k)>{{ $l }}</option>@endforeach</select>
        </label>
        <label data-for="attestation_formation">Date d’inscription <input type="date" name="details[inscription]" value="{{ $dt('inscription') }}"></label>
        <label data-for="attestation_formation">Fin de formation (si achevée) <input type="date" name="details[fin_formation]" value="{{ $dt('fin_formation') }}"></label>
        <label data-for="attestation_formation">Contenu de la formation <input name="details[contenu]" value="{{ $dt('contenu') }}" placeholder="ex. Adobe Photoshop, Illustrator et InDesign"></label>
        <label class="full" data-for="attestation_formation">Format <input name="details[format]" value="{{ $dt('format') }}" placeholder="ex. Hybride : en ligne et en présentiel, Abidjan"></label>

        <label class="full" data-for="attestation_stage">Poste occupé <input name="details[poste]" value="{{ $dt('poste') }}" placeholder="ex. Stagiaire en Design graphique et Community Management"></label>
        <label data-for="attestation_stage">Début du stage <input type="date" name="details[debut]" value="{{ $dt('debut') }}"></label>
        <label data-for="attestation_stage">Fin du stage <input type="date" name="details[fin]" value="{{ $dt('fin') }}"></label>
        <label class="full" data-for="attestation_stage">Missions (une par ligne, au format « Titre : description »)
            <textarea name="details[missions]" rows="6" placeholder="Conception graphique : réalisation des badges étudiants et des supports visuels…">{{ $dt('missions') }}</textarea></label>
        <label class="full" data-for="attestation_stage">Appréciation (facultatif, sinon texte type) <textarea name="details[appreciation]" rows="2">{{ $dt('appreciation') }}</textarea></label>

        <label class="full" data-for="recommandation">Destinataire <input name="details[destinataire]" value="{{ $dt('destinataire') }}" placeholder="À l’attention des recruteurs et de toute personne concernée,"></label>
        <label class="full" data-for="recommandation">Corps de la lettre (paragraphes séparés par une ligne vide ; vide = texte type)
            <textarea name="details[corps]" id="corps" rows="10">{{ $dt('corps') }}</textarea></label>
        <div class="full row" data-for="recommandation"><button type="button" class="btn ghost" id="tplText">Insérer le texte type</button><span class="fileinfo">Le texte type reprend le nom, la formation et le n° de certificat du titulaire. Personnalisez-le ensuite.</span></div>

        <label class="full" data-for="communique">Titre du communiqué (le fait principal en une phrase) <input name="details[objet]" value="{{ $dt('objet') }}" placeholder="ex. Ouverture des inscriptions — Promotion 2027"></label>
        <label class="full" data-for="communique">Destinataires <input name="details[destinataires]" value="{{ $dt('destinataires') }}" placeholder="ex. Apprenants de la formation Infographie et Design Graphique"></label>
        <div class="full" data-for="communique">
            <label style="margin-bottom:6px;display:block">Contenu du communiqué <span class="fileinfo">— ouverture, points numérotés (liste « 01. »), citation en bloc, etc.</span></label>
            <div id="contenu-editor" style="min-height:300px;background:#fff;border:1px solid #d9dfe8;border-radius:8px"></div>
            <textarea name="details[contenu]" id="contenu-input" style="display:none">{{ $dt('contenu') }}</textarea>
            <span class="fileinfo" id="contenu-count">0 / 1400 caractères (recommandé pour tenir sur une page)</span>
        </div>
        @foreach ($errors->get('details.*') as $msgs)<div class="err-text full">{{ $msgs[0] }}</div>@endforeach

        <div class="full notice" id="next-ref">Prochaine référence prévue : <strong>…</strong> <span class="fileinfo">(indicative ; attribuée définitivement à la délivrance)</span></div>

        <div class="full row">
            <button class="btn" type="submit">{{ $document->exists ? 'Enregistrer' : 'Créer le brouillon' }}</button>
            <a class="btn ghost" href="{{ $document->exists ? route('admin.documents.show', $document) : route('admin.documents.index') }}">Annuler</a>
        </div>
    </form>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {
    var contenuInput = document.getElementById('contenu-input'), quill = null;
    if (contenuInput && window.Quill) {
        quill = new Quill('#contenu-editor', {
            theme: 'snow',
            placeholder: 'Collez ou rédigez le contenu du communiqué…',
            modules: { toolbar: [['bold', 'italic', 'underline', 'strike'], [{ 'list': 'ordered' }], ['blockquote'], [{ 'color': [] }], ['clean']] }
        });
        if (contenuInput.value.trim()) quill.clipboard.dangerouslyPasteHTML(contenuInput.value);
        var counter = document.getElementById('contenu-count');
        function updateCount() {
            var n = quill.getText().replace(/\n+/g, ' ').trim().length;
            counter.textContent = n + ' / 1400 caractères (recommandé pour tenir sur une page)';
            counter.style.color = n > 1400 ? '#c0392b' : '';
        }
        quill.on('text-change', updateCount); updateCount();
        contenuInput.closest('form').addEventListener('submit', function () {
            contenuInput.value = quill.getText().trim() ? quill.root.innerHTML : '';
        });
    }

    var type = document.getElementById('type'), formation = document.getElementById('formation'), box = document.querySelector('#next-ref strong');
    function refresh() {
        var url = @json(route('admin.documents.next')) + '?type=' + type.value + '&formation=' + encodeURIComponent(formation.value);
        fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); })
            .then(function (j) { box.textContent = j.reference || '—'; }).catch(function () { box.textContent = '—'; });
    }
    type.addEventListener('change', refresh); formation.addEventListener('change', refresh); refresh();
    // Champs propres au type de document
    var codes = @json($types->pluck('code', 'id'));
    function toggleFields() {
        var code = codes[type.value];
        document.querySelectorAll('[data-for]').forEach(function (el) {
            var on = el.dataset.for.split(' ').indexOf(code) !== -1;
            el.style.display = on ? '' : 'none';
            el.querySelectorAll('input,select,textarea').forEach(function (i) { i.disabled = !on; });
        });
    }
    type.addEventListener('change', toggleFields); toggleFields();
    var tb = document.getElementById('tplText');
    if (tb) tb.addEventListener('click', function () {
        var hid = document.getElementById('holder-id'), params = new URLSearchParams({
            holder: hid ? hid.value : @json($document->holder_id), name: (document.querySelector('[name=holder_name]') || {}).value || '',
            formation: formation.value, civilite: document.querySelector('[name="details[civilite]"]').value,
            signataire: document.querySelector('[name="details[signataire]"]').value });
        fetch(@json(route('admin.documents.template-text')) + '?' + params, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (j) {
                var c = document.getElementById('corps');
                if (!c.value.trim() || confirm('Remplacer le texte actuel par le texte type ?')) c.value = j.text;
            });
    });


    var search = document.getElementById('holder-search');
    if (!search) return;
    var list = document.getElementById('holder-list'), hid = document.getElementById('holder-id'), state = document.getElementById('holder-state'), cache = {}, t;
    search.addEventListener('input', function () {
        var v = search.value.trim();
        if (cache[v]) { hid.value = cache[v]; state.textContent = 'Titulaire sélectionné : ' + v; return; }
        hid.value = ''; state.textContent = v ? 'Aucun titulaire sélectionné (choisissez dans la liste).' : 'Aucun titulaire sélectionné.';
        clearTimeout(t); if (v.length < 2) return;
        t = setTimeout(function () {
            fetch(@json(route('admin.beneficiaires.index')) + '?q=' + encodeURIComponent(v), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); }).then(function (rows) {
                    list.innerHTML = '';
                    rows.forEach(function (h) {
                        var label = h.full_name + (h.external_id ? ' — ' + h.external_id : '');
                        cache[label] = h.id; var o = document.createElement('option'); o.value = label; list.appendChild(o);
                    });
                });
        }, 250);
    });
})();
</script>
@endpush
