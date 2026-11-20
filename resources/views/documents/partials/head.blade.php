<div class="head"><img class="logo" src="{{ $asset('logo-evc.png') }}" alt="EVC"><div class="inst b">École Virtuelle des Créatifs</div><div class="place b">Abidjan · République de Côte d’Ivoire</div><div class="rule"></div></div>
<div class="title b" @if (mb_strlen($title) > 24) style="font-size:18pt;letter-spacing:2.6pt" @endif>{{ $title }}</div>
@if (! empty($sub))<div class="sub">{{ $sub }}</div>@endif
<div class="ref">N° <b>{{ $c['reference'] ?: 'attribuée à la délivrance' }}</b></div>
