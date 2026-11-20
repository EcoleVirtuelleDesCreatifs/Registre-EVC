@php
    $sigFile = 'signature-'.($signataire ?? 'dg').'.png';
    $hasSignature = file_exists(public_path('documents-assets/'.$sigFile));
    $hasStamp = ($stamp ?? false) && file_exists(public_path('documents-assets/cachet.png'));
@endphp
@if ($hasSignature)
    <div class="sig-img"><img src="{{ $asset($sigFile) }}" alt=""></div>
@endif
@if ($hasStamp)
    <img class="stamp" src="{{ $asset('cachet.png') }}" alt="">
@endif
