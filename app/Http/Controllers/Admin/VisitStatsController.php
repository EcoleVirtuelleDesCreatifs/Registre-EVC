<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use App\Models\Verification;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Statistiques de consultation du registre public
 * (verification.ecolevirtuelledescreatifs.com).
 */
class VisitStatsController extends Controller
{
    public const PERIODS = ['7' => '7 jours', '30' => '30 jours', '90' => '3 mois', '365' => '12 mois'];

    public function index(Request $request)
    {
        Gate::authorize('stats.view');

        $days = (int) (array_key_exists((string) $request->input('periode'), self::PERIODS) ? $request->input('periode') : 30);
        $to = CarbonImmutable::now()->endOfDay();
        $from = $to->subDays($days - 1)->startOfDay();
        $prevTo = $from->subSecond();
        $prevFrom = $prevTo->subDays($days - 1)->startOfDay();
        $monthly = $days > 92;

        $visits = fn () => SiteVisit::whereBetween('created_at', [$from, $to]);
        $verifs = fn () => Verification::whereBetween('created_at', [$from, $to]);

        // Indicateurs clés et comparaison avec la période précédente
        $kpi = fn (CarbonImmutable $a, CarbonImmutable $b) => [
            'pages' => SiteVisit::whereBetween('created_at', [$a, $b])->count(),
            'visiteurs' => SiteVisit::whereBetween('created_at', [$a, $b])->distinct()->count('visitor_hash'),
            'verifications' => Verification::whereBetween('created_at', [$a, $b])->where('method', '!=', 'api')->count(),
            'valides' => Verification::whereBetween('created_at', [$a, $b])->where('method', '!=', 'api')->where('result', 'valide')->count(),
            'qr' => Verification::whereBetween('created_at', [$a, $b])->where('method', 'qr')->count(),
            'pdf' => SiteVisit::whereBetween('created_at', [$a, $b])->where('page', 'pdf')->count(),
        ];
        $now = $kpi($from, $to);
        $prev = $kpi($prevFrom, $prevTo);
        $now['taux'] = $now['verifications'] ? round($now['valides'] / $now['verifications'] * 100) : null;
        $prev['taux'] = $prev['verifications'] ? round($prev['valides'] / $prev['verifications'] * 100) : null;

        // Évolution (jour par jour, ou mois par mois sur 12 mois)
        $bucket = $monthly ? $this->expr('month') : $this->expr('day');
        $raw = $visits()->selectRaw("{$bucket} as b, count(*) as pages, count(distinct visitor_hash) as visiteurs")
            ->groupBy('b')->get()->keyBy(fn ($r) => (string) $r->b);
        $rawVerif = $verifs()->where('method', '!=', 'api')->selectRaw("{$bucket} as b, count(*) as n")->groupBy('b')->pluck('n', 'b');

        $series = [];
        $steps = $monthly
            ? CarbonPeriod::create($from->startOfMonth(), '1 month', $to->startOfMonth())
            : CarbonPeriod::create($from, '1 day', $to->startOfDay());
        foreach ($steps as $d) {
            $key = $monthly ? $d->format('Y-m') : $d->format('Y-m-d');
            $series[] = [
                'key' => $key,
                'label' => $monthly ? $d->locale('fr')->translatedFormat('M Y') : $d->locale('fr')->translatedFormat('D j M'),
                'short' => $monthly ? $d->locale('fr')->translatedFormat('M') : $d->format('d/m'),
                'pages' => (int) ($raw[$key]->pages ?? 0),
                'visiteurs' => (int) ($raw[$key]->visiteurs ?? 0),
                'verifications' => (int) ($rawVerif[$key] ?? 0),
            ];
        }

        // Moments de consultation
        $hours = $visits()->selectRaw($this->expr('hour').' as h, count(*) as n')->groupBy('h')->pluck('n', 'h');
        $weekdays = $visits()->selectRaw($this->expr('weekday').' as w, count(*) as n')->groupBy('w')->pluck('n', 'w');

        $top = fn (string $col, int $limit = 8, ?callable $scope = null) => $visits()
            ->when($scope, $scope)
            ->selectRaw("{$col} as k, count(*) as n, count(distinct visitor_hash) as v")
            ->groupBy('k')->orderByDesc('n')->limit($limit)->get();

        return view('admin.stats.visits', [
            'days' => $days,
            'from' => $from,
            'to' => $to,
            'monthly' => $monthly,
            'now' => $now,
            'prev' => $prev,
            'series' => $series,
            'hours' => collect(range(0, 23))->mapWithKeys(fn ($h) => [$h => (int) ($hours[$h] ?? 0)]),
            'weekdays' => collect(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'])->mapWithKeys(fn ($l, $i) => [$l => (int) ($weekdays[$i] ?? 0)]),
            'pages' => $top('page', 10),
            'devices' => $top('device'),
            'browsers' => $top('browser'),
            'systems' => $top('os'),
            'langs' => $top('lang', 6, fn (Builder $q) => $q->whereNotNull('lang')),
            'sources' => $top('referrer', 10, fn (Builder $q) => $q->whereNotNull('referrer')->where('referrer', '!=', '(interne)')),
            'direct' => $visits()->whereNull('referrer')->count(),
            'results' => $verifs()->where('method', '!=', 'api')->select('result', DB::raw('count(*) as n'))->groupBy('result')->pluck('n', 'result'),
            'methods' => $verifs()->select('method', DB::raw('count(*) as n'))->groupBy('method')->pluck('n', 'method'),
            'topDocs' => $verifs()->whereNotNull('document_id')
                ->select('document_id', DB::raw('count(*) as n'), DB::raw('max(created_at) as last_at'))
                ->groupBy('document_id')->orderByDesc('n')->limit(8)
                ->with('document.holder', 'document.type')->get(),
            'unknown' => $verifs()->where('result', 'introuvable')
                ->select('query', DB::raw('count(*) as n'), DB::raw('max(created_at) as last_at'))
                ->groupBy('query')->orderByDesc('n')->limit(8)->get(),
            // Visiteurs dont le volume de vérifications ressemble à une recherche automatisée
            'suspects' => $verifs()->whereNotNull('ip_hash')
                ->select('ip_hash', DB::raw('count(*) as n'), DB::raw("sum(case when result = 'introuvable' then 1 else 0 end) as ko"), DB::raw('max(created_at) as last_at'))
                ->groupBy('ip_hash')->havingRaw('count(*) >= 15')->orderByDesc('n')->limit(5)->get(),
            'recent' => Verification::with('document.holder')->where('method', '!=', 'api')->latest('id')->limit(12)->get(),
        ]);
    }

    /** Expressions SQL de regroupement compatibles MySQL / MariaDB et SQLite. */
    private function expr(string $what): string
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';

        return match ($what) {
            'day' => $sqlite ? "date(created_at)" : 'DATE(created_at)',
            'month' => $sqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')",
            'hour' => $sqlite ? "cast(strftime('%H', created_at) as integer)" : 'HOUR(created_at)',
            'weekday' => $sqlite ? "((cast(strftime('%w', created_at) as integer) + 6) % 7)" : 'WEEKDAY(created_at)',
        };
    }
}
