<?php

namespace App\Http\Controllers;

use App\Models\OfficialDocument;
use App\Models\Verification;
use App\Support\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PublicController extends Controller
{
    public function index()
    {
        return view('public.index');
    }

    public function privacy()
    {
        return view('public.privacy');
    }

    public function check(Request $request)
    {
        $reference = Reference::normalize($request->input('reference'));

        if ($reference === '') {
            return redirect()->route('public.index')->withErrors(['reference' => 'Saisissez la référence imprimée sur le document.']);
        }
        if (strlen($reference) > 40) {
            return redirect()->route('public.index')->withErrors(['reference' => 'Cette référence est trop longue.'])->withInput();
        }

        return redirect()->route('public.show', $reference);
    }

    public function show(Request $request, string $reference)
    {
        $reference = Reference::normalize($reference);

        return $this->result($request, fn () => OfficialDocument::with(['type', 'holder', 'revocation'])
            ->where('reference', $reference)->first(), $reference, 'reference');
    }

    /** Accès par QR code : identifiant aléatoire, impossible à deviner. */
    public function qr(Request $request, string $token)
    {
        return $this->result($request, fn () => OfficialDocument::with(['type', 'holder', 'revocation'])
            ->where('public_token', $token)->first(), null, 'qr');
    }

    public function pdf(Request $request, string $reference): Response
    {
        $doc = $this->publishedDocument($reference);
        abort_unless($doc->pdf_public && $doc->pdf_path && Storage::disk('local')->exists($doc->pdf_path), 404);

        return Storage::disk('local')->response($doc->pdf_path, $doc->reference.'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'private, no-store',
        ], $request->boolean('telecharger') ? 'attachment' : 'inline');
    }

    public function preview(string $reference): Response
    {
        $doc = $this->publishedDocument($reference);
        abort_unless($doc->pdf_public && $doc->preview_path && Storage::disk('local')->exists($doc->preview_path), 404);

        return Storage::disk('local')->response($doc->preview_path, null, [
            'X-Robots-Tag' => 'noindex',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    private function result(Request $request, callable $finder, ?string $reference, string $method)
    {
        try {
            $doc = $finder();
            // Seuls les documents publiés ou révoqués existent pour le public
            if ($doc && ! $doc->isPublic()) {
                $doc = null;
            }
            $result = ! $doc ? 'introuvable' : ($doc->status === 'revoque' ? 'revoque' : 'valide');

            Verification::create([
                'query' => mb_substr($reference ?? ($doc?->reference ?? 'QR'), 0, 80),
                'method' => $method,
                'document_id' => $doc?->id,
                'result' => $result,
                'ip_hash' => hash('sha256', $request->ip().config('app.key')),
            ]);
        } catch (Throwable $e) {
            Log::error('Vérification indisponible', ['error' => $e->getMessage()]);

            return response()->view('public.result', ['result' => 'indisponible', 'doc' => null, 'reference' => $reference], 503)
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return response()->view('public.result', [
            'result' => $result,
            'doc' => $doc,
            'reference' => $doc?->reference ?? $reference,
            'viaQr' => $method === 'qr',
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function publishedDocument(string $reference): OfficialDocument
    {
        return OfficialDocument::where('reference', Reference::normalize($reference))
            ->where('status', 'publie')->firstOrFail();
    }
}
