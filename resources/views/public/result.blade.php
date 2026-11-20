@extends('layouts.app')

@section('title', 'Résultat de la vérification')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="card">
        <form class="search" method="POST" action="{{ route('public.check') }}">
            @csrf
            <input name="reference" value="{{ $reference }}" placeholder="Référence, ex. : EVC-DG-2026-0007" aria-label="Référence du document" maxlength="40" required>
            <button class="btn" type="submit">Vérifier</button>
        </form>
    </div>

    @if ($result === 'indisponible')
        <div class="res info">
            <span class="badge">Vérification indisponible</span>
            <h2>Le registre ne peut pas être consulté pour le moment</h2>
            <p>Un problème technique empêche temporairement la vérification. Ce message ne constitue aucun jugement sur l’authenticité du document. Réessayez dans quelques minutes.</p>
        </div>

    @elseif ($result === 'introuvable')
        <div class="res bad">
            <span class="badge">Document introuvable</span>
            <h2>{{ $reference ?: 'QR code non reconnu' }}</h2>
            <p>Aucune référence correspondante n’a été retrouvée dans le registre officiel. Cela ne prouve pas automatiquement une fraude : vérifiez la saisie (lettres, tirets, chiffres) ou contactez l’EVC pour une vérification complémentaire.</p>
        </div>

    @elseif ($result === 'revoque')
        <div class="res bad">
            <span class="badge">✕ Document révoqué</span>
            <h2>{{ $doc->holder->full_name }}</h2>
            <p>Ce document a été annulé par une décision administrative officielle de l’École Virtuelle des Créatifs. Il ne doit plus être accepté comme justificatif.</p>
            <div class="grid">
                <div class="k">Référence</div><div class="v">{{ $doc->reference }}</div>
                <div class="k">Document</div><div class="v">{{ $doc->type->label }}</div>
                <div class="k">Délivré le</div><div class="v">{{ $doc->issuedFr() }}</div>
                @if ($doc->revocation)<div class="k">Révoqué le</div><div class="v">{{ $doc->revocation->revoked_at->locale('fr')->translatedFormat('j F Y') }}</div>@endif
                <div class="k">Statut</div><div class="v">Révoqué</div>
            </div>
        </div>

    @else
        <div class="res ok">
            <span class="badge">✓ Document authentifié et valide</span>
            <h2>{{ $doc->holder->full_name }}</h2>
            <p class="muted" style="margin:0">La référence correspond à un document actif enregistré dans le registre officiel.</p>
            <div class="grid">
                <div class="k">Référence</div><div class="v">{{ $doc->reference }}</div>
                <div class="k">Document</div><div class="v">{{ $doc->type->label }}</div>
                @if ($doc->formationLabel())<div class="k">Formation</div><div class="v">{{ $doc->formationLabel() }}</div>@endif
                <div class="k">Délivré le</div><div class="v">{{ $doc->issuedFr() }}</div>
                <div class="k">Statut</div><div class="v">Valide</div>
            </div>

            <div class="compare">
                <strong>Comparez</strong> le nom, la formation, la date et la référence avec le document que vous avez entre les mains.
                La vérification dans le registre confirme la délivrance enregistrée par l’EVC, mais ne garantit pas à elle seule que le document présenté n’a subi aucune modification :
                @if ($doc->pdf_public && $doc->pdf_path) comparez-le avec l’exemplaire officiel ci-dessous. @else en cas de doute, contactez l’EVC. @endif
            </div>


            @if ($doc->pdf_sha256)
                <div class="compare" style="background:#fff;border:1px solid var(--line)">
                    <strong>Contrôle d’intégrité du fichier</strong> — vous avez reçu ce document en PDF ? Sélectionnez-le : votre navigateur calcule son empreinte numérique et la compare à celle de l’exemplaire officiel. Le fichier n’est pas envoyé.
                    <div class="row" style="margin-top:10px"><input type="file" id="integrity" accept="application/pdf"></div>
                    <div id="integrity-result" class="small" style="margin-top:8px"></div>
                    <div class="fileinfo" style="margin-top:6px;word-break:break-all">Empreinte officielle (SHA-256) : {{ $doc->pdf_sha256 }}</div>
                </div>
                @push('scripts')
                <script>
                document.getElementById('integrity').addEventListener('change', function () {
                    var f = this.files[0], out = document.getElementById('integrity-result');
                    if (!f || !window.crypto || !crypto.subtle) { out.textContent = 'Contrôle impossible avec ce navigateur.'; return; }
                    f.arrayBuffer().then(function (b) { return crypto.subtle.digest('SHA-256', b); }).then(function (h) {
                        var hex = Array.from(new Uint8Array(h)).map(function (x) { return x.toString(16).padStart(2, '0'); }).join('');
                        var ok = hex === @json($doc->pdf_sha256) || hex === @json($doc->generated_sha256);
                        out.innerHTML = ok ? '<strong style="color:var(--ok)">✓ Fichier identique à l’exemplaire officiel enregistré.</strong>'
                            : '<strong style="color:var(--bad)">✕ Ce fichier diffère de l’exemplaire officiel.</strong> Il a pu être modifié, ou il s’agit d’un scan ou d’une autre version. Comparez-le visuellement avec l’exemplaire ci-dessous ou contactez l’EVC.';
                    });
                });
                </script>
                @endpush
            @endif
            @if ($doc->pdf_public && ($doc->pdf_path || $doc->preview_path))
                <div class="doc">
                    <div class="lbl">Exemplaire officiel enregistré</div>
                    @if ($doc->preview_path)
                        <img src="{{ route('public.preview', $doc->reference) }}" alt="Aperçu du document {{ $doc->reference }}">
                    @elseif ($doc->pdf_path)
                        <iframe src="{{ route('public.pdf', [$doc->reference, 'v' => substr($doc->generated_sha256 ?? $doc->pdf_sha256 ?? '', 0, 12)]) }}#view=FitH" title="Document {{ $doc->reference }}"></iframe>
                    @endif
                    @if ($doc->pdf_path)
                        <div class="acts">
                            <a class="btn gold" href="{{ route('public.pdf', [$doc->reference, 'telecharger' => 1]) }}">Télécharger le PDF</a>
                            <a class="btn ghost" href="{{ route('public.pdf', [$doc->reference, 'v' => substr($doc->generated_sha256 ?? $doc->pdf_sha256 ?? '', 0, 12)]) }}" target="_blank" rel="noopener">Ouvrir le PDF</a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @include('partials.contact')
@endsection
