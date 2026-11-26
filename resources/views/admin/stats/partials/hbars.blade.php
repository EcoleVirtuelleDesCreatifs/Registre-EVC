{{-- Barres horizontales : $items = [['label' => …, 'n' => …, 'sub' => …, 'color' => …]], $unit --}}
@php
    $total = max(1, collect($items)->sum('n'));
    $max = max(1, collect($items)->max('n'));
@endphp
@if (collect($items)->sum('n') === 0)
    <p class="vs-none">Aucune donnée sur la période.</p>
@else
    <div class="vs-hbars">
        @foreach ($items as $it)
            @php $pct = round($it['n'] / $total * 100); @endphp
            <div class="vs-hbar" data-tip="{{ $it['label'] }}|{{ number_format($it['n'], 0, ',', ' ') }} {{ $unit ?? 'pages vues' }} · {{ $pct }} %@if (! empty($it['sub']))|{{ $it['sub'] }}@endif">
                <span class="l">@if (! empty($it['icon']))<span class="ico" aria-hidden="true">{{ $it['icon'] }}</span>@endif{{ $it['label'] }}</span>
                <span class="t"><i style="width:{{ max(1.5, $it['n'] / $max * 100) }}%;{{ ! empty($it['color']) ? 'background:'.$it['color'] : '' }}"></i></span>
                <b>{{ number_format($it['n'], 0, ',', ' ') }}</b>
                <span class="p">{{ $pct }} %</span>
            </div>
        @endforeach
    </div>
@endif
