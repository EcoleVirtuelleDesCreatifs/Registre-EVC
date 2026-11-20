{{-- QR code généré sur le serveur (PNG). Paramètres : $text, $id, $filename (facultatif, active le téléchargement) --}}
@php $qrImage = app(\App\Services\DocumentRenderer::class)->qrDataUri($text, 10); @endphp
<div class="qr" id="{{ $id }}" aria-label="QR code">
    @if ($qrImage)<img src="{{ $qrImage }}" alt="QR code" style="width:100%;height:100%;display:block;image-rendering:pixelated">@else<span class="fileinfo">QR indisponible (extension PHP gd ou bibliothèque QR manquante).</span>@endif
</div>
@if (! empty($filename) && $qrImage)
    <div class="acts" style="margin-top:8px">
        <a class="btn ghost" href="{{ $qrImage }}" download="{{ $filename }}">Télécharger le QR (PNG)</a>
    </div>
@endif
