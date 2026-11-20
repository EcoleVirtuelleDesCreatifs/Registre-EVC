<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use App\Models\Verification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index()
    {
        Gate::authorize('stats.view');

        $byStatus = OfficialDocument::select('status', DB::raw('count(*) as n'))->groupBy('status')->pluck('n', 'status');
        $published = OfficialDocument::whereIn('status', ['publie', 'revoque'])->whereNotNull('issued_at');

        $byFormation = (clone $published)->select('formation', DB::raw('count(*) as n'))->groupBy('formation')->pluck('n', 'formation');
        $byType = (clone $published)->select('document_type_id', DB::raw('count(*) as n'))->groupBy('document_type_id')->pluck('n', 'document_type_id');
        $years = (clone $published)->get(['issued_at'])->groupBy(fn ($d) => $d->issued_at->format('Y'))->map->count()->sortKeys();
        $months = (clone $published)->whereYear('issued_at', now()->year)->get(['issued_at'])
            ->groupBy(fn ($d) => (int) $d->issued_at->format('n'))->map->count();

        return view('admin.dashboard', [
            'counts' => [
                'publies' => $byStatus['publie'] ?? 0,
                'a_controler' => OfficialDocument::where('status', 'en_verification')->whereNull('pedagogical_validated_at')->count(),
                'a_approuver' => OfficialDocument::where('status', 'en_verification')->whereNotNull('pedagogical_validated_at')->count(),
                'a_delivrer' => OfficialDocument::where('status', 'approuve')->count(),
                'brouillons' => $byStatus['brouillon'] ?? 0,
                'rejetes' => $byStatus['rejete'] ?? 0,
                'revoques' => $byStatus['revoque'] ?? 0,
                'sans_pdf' => OfficialDocument::where('status', 'publie')->whereNull('pdf_path')->count(),
            ],
            'byFormation' => $byFormation,
            'byType' => $byType,
            'types' => DocumentType::ordered()->keyBy('id'),
            'years' => $years,
            'months' => $months,
            'verif' => [
                'total30' => Verification::where('created_at', '>=', now()->subDays(30))->count(),
                'valides30' => Verification::where('created_at', '>=', now()->subDays(30))->where('result', 'valide')->count(),
                'introuvables30' => Verification::where('created_at', '>=', now()->subDays(30))->where('result', 'introuvable')->count(),
                'qr30' => Verification::where('created_at', '>=', now()->subDays(30))->where('method', 'qr')->count(),
            ],
            'unknown' => Verification::where('result', 'introuvable')->where('created_at', '>=', now()->subDays(30))
                ->select('query', DB::raw('count(*) as n'))->groupBy('query')->orderByDesc('n')->limit(8)->get(),
        ]);
    }
}
