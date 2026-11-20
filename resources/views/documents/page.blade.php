{{-- Document officiel EVC : page complète (aperçu écran ou rendu PDF par dompdf). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
@page { size: A4 {{ $landscape ? 'landscape' : 'portrait' }}; margin: 0; }
@font-face { font-family: 'Lora'; src: url('{{ $asset('fonts/Lora-Regular.ttf') }}') format('truetype'); font-weight: normal; font-style: normal; }
@font-face { font-family: 'Lora'; src: url('{{ $asset('fonts/Lora-Italic.ttf') }}') format('truetype'); font-weight: normal; font-style: italic; }
@font-face { font-family: 'Lora'; src: url('{{ $asset('fonts/Lora-Bold.ttf') }}') format('truetype'); font-weight: bold; font-style: normal; }
@font-face { font-family: 'Lora'; src: url('{{ $asset('fonts/Lora-BoldItalic.ttf') }}') format('truetype'); font-weight: bold; font-style: italic; }
@font-face { font-family: 'Libre Baskerville'; src: url('{{ $asset('fonts/LibreBaskerville-Regular.ttf') }}') format('truetype'); font-weight: normal; font-style: normal; }
html, body { margin: 0; padding: 0; }
@if ($pdf)
.dp.l { height: 209.6mm; } .dp.p { height: 296.6mm; }
@else
body { background: {{ $embed ? '#fff' : '#e9e4d8' }}; }
.sheet { {{ $embed ? 'margin:0!important;box-shadow:none!important;' : '' }} width: {{ $landscape ? '297mm' : '210mm' }}; margin: 24px auto; box-shadow: 0 10px 40px rgba(15,29,61,.18); }
.bar { position: sticky; top: 0; z-index: 5; background: #0f1d3d; color: #fff; font: 14px/1.4 system-ui, sans-serif; padding: 10px 18px; display: flex; gap: 12px; align-items: center; justify-content: center; }
.bar button { font: inherit; background: #c9a24a; color: #0f1d3d; border: 0; border-radius: 8px; padding: 7px 14px; font-weight: 600; cursor: pointer; }
@media print { body { background: none; } .sheet { margin: 0; box-shadow: none; } .bar { display: none; } }
@endif
</style>
@include('documents.partials.styles')
@if ($pdf)
<style>
/* Le moteur PDF calcule des interlignes plus grands que le navigateur : on les resserre pour un rendu identique. */
.dp .cc .inst { line-height: 1.05; } .dp .cc .place { line-height: 1.2; }
.dp .cc .lead { margin-top: 5mm; line-height: 1.22; }
.dp .cc .name { margin-top: 2mm; line-height: 1; }
.dp .cc .lead2 { margin-top: 1.2mm; line-height: 1.2; }
.dp .cc .title { line-height: 1.12; } .dp .cc .prog { line-height: 1.2; } .dp .cc .mention { line-height: 1.2; }
.dp .cc .body { margin-top: 1.6mm; line-height: 1.2; }
.dp .cc .formula { margin-top: 2.2mm; line-height: 1.28; }
.dp .csig .who, .dp .csig .role, .dp .asig .who, .dp .asig .role, .dp .asig .t { line-height: 1.25; }
.dp .ac .pp { line-height: 1.38; } .dp .ac .nm { line-height: 1.15; }
.dp .ac table.card td { line-height: 1.3; } .dp .ac .note { line-height: 1.35; }
.dp .ac .intro { line-height: 1.3; } .dp .ac table.ms td { line-height: 1.3; }
.dp .ac .formula { line-height: 1.38; } .dp .abot .date { line-height: 1.3; }
.dp .lbody p, .dp .lbody .closing { line-height: 1.42; } .dp .lfoot { line-height: 1.3; }
.dp .qrbox .ph { padding-top: 0; line-height: 1.25; }
.dp .cc { top: 13mm; } .dp .cc .logo { height: 11mm; } .dp .cc .inst { margin-top: 1.5mm; }
.dp .cc .lead { margin-top: 4mm; } .dp .cc .title { margin-top: .5mm; }
.dp .seal { bottom: 27mm; }
</style>
@endif
</head>
<body>
@unless ($pdf || $embed)
    <div class="bar">{{ $title }} @if ($ctx['specimen']) — aperçu de travail, non valable @endif <button type="button" onclick="window.print()">Imprimer</button></div>
@endunless
<div class="sheet">@include('documents.'.$ctx['type'], ['c' => $ctx, 'asset' => $asset])</div>
</body>
</html>
