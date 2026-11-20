<div class="qrbox" style="{{ $pos }};width:{{ $size }}">
    @if ($c['qr'])<img src="{{ $c['qr'] }}" alt="QR">@else<div class="ph" style="height:{{ $size }}">QR code<br>{{ $c['reference'] ?: 'à la délivrance' }}</div>@endif
    @if ($caption ?? true)<div class="cap">Scanner pour<br>vérifier</div>@endif
</div>
