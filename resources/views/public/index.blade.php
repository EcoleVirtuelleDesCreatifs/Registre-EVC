@extends('layouts.app')

@section('title', 'Registre officiel de vérification des documents')

@section('content')
    <div class="hero-kicker">École Virtuelle des Créatifs</div>
    <h1>Registre officiel de vérification des documents</h1>
    <p class="lead" style="margin-bottom:8px">Vérifiez l’authenticité des certificats professionnels, attestations et lettres de recommandation délivrés par l’École Virtuelle des Créatifs.</p>
    <div class="official">🔒 Service officiel de consultation documentaire</div>

    <div class="card" style="margin-top:22px">
        <form class="search" method="POST" action="{{ route('public.check') }}" novalidate>
            @csrf
            <input name="reference" value="{{ old('reference') }}" placeholder="Référence, ex. : EVC-DG-2026-0007" autocomplete="off" autofocus
                   aria-label="Référence du document" maxlength="40" required>
            <button class="btn" type="submit">Vérifier</button>
        </form>
        @error('reference')<div class="err-text" style="margin-top:8px">{{ $message }}</div>@enderror
        <div class="hint">La référence figure en bas du document. Les minuscules et les espaces sont acceptés. Vous pouvez aussi scanner le QR code imprimé sur le document.</div>
    </div>

    <div class="how">
        <div><b>1</b>Chaque document délivré par l’EVC porte une référence unique, enregistrée dans ce registre au moment de sa délivrance.</div>
        <div><b>2</b>Saisissez la référence ou scannez le QR code : le registre affiche le titulaire, le document, la formation, la date et le statut.</div>
        <div><b>3</b><strong>Des informations différentes ?</strong> Si les données du registre ne correspondent pas à celles du document présenté, son authenticité ne peut pas être confirmée. Contactez l’EVC pour une vérification complémentaire.</div>
    </div>

    @include('partials.contact')
@endsection

@push('scripts')
<script>
    // Empêche une recherche vide ; la touche Entrée déclenche la vérification
    document.querySelector('form.search').addEventListener('submit', function (e) {
        var i = this.querySelector('input[name=reference]');
        i.value = i.value.trim();
        if (!i.value) { e.preventDefault(); i.focus(); }
    });
</script>
@endpush
