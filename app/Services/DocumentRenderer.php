<?php

namespace App\Services;

use App\Models\OfficialDocument;
use App\Models\User;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Génère les documents officiels (certificat, attestations, lettre) à partir
 * des gabarits resources/views/documents, en HTML (aperçu) ou en PDF (dompdf).
 */
class DocumentRenderer
{
    private const LANDSCAPE = ['certificat'];

    /** Données transmises au gabarit. */
    public function context(OfficialDocument $doc, bool|string $specimen = false): array
    {
        $doc->loadMissing(['type', 'holder']);
        $details = array_merge($this->defaults($doc), array_filter($doc->details ?? [], fn ($v) => ! blank($v)));
        $issued = $doc->issued_at?->toDateString();

        return [
            'type' => $doc->type->code,
            'name' => $doc->holder->full_name,
            'formationLabel' => $doc->formationLabel(),
            'reference' => $doc->reference,
            'issued' => $issued,
            'year' => substr($issued ?? now()->toDateString(), 0, 4),
            'details' => $details,
            'qr' => $doc->reference && $doc->public_token ? $this->qr(route('public.qr', $doc->public_token)) : null,
            'specimen' => $specimen,
            'certificate' => $doc->type->code === 'recommandation' ? $this->certificateOf($doc) : null,
        ];
    }

    /** Valeurs par défaut des champs de contenu. */
    public function defaults(OfficialDocument $doc): array
    {
        return [
            'civilite' => 'M',
            'signataire' => 'dg',
            'statut' => 'en_cours',
            'contenu' => config('evc.contenus_defaut.'.$doc->formation) ?: null,
        ];
    }

    /** Aperçu de travail tant que la référence n'est pas attribuée ; mention de révocation ensuite. */
    public function watermarkFor(OfficialDocument $doc): bool|string
    {
        if ($doc->status === 'revoque') {
            return 'DOCUMENT RÉVOQUÉ';
        }

        return $doc->reference ? false : true;
    }

    public function html(OfficialDocument $doc, bool $pdf = false, bool|string|null $specimen = null, bool $embed = false): string
    {
        $ctx = $this->context($doc, $specimen ?? $this->watermarkFor($doc));
        $asset = $pdf
            ? fn (string $f) => public_path('documents-assets/'.$f)
            : fn (string $f) => asset('documents-assets/'.$f);

        return view('documents.page', [
            'ctx' => $ctx,
            'asset' => $asset,
            'pdf' => $pdf,
            'embed' => $embed,
            'landscape' => in_array($ctx['type'], self::LANDSCAPE, true),
            'title' => $doc->type->label.' — '.$ctx['name'].($ctx['reference'] ? ' — '.$ctx['reference'] : ''),
        ])->render();
    }

    public function pdf(OfficialDocument $doc, bool|string|null $specimen = null): string
    {
        $options = new Options();
        $options->setChroot([public_path('documents-assets')]);
        $options->setIsRemoteEnabled(false);
        $options->setIsFontSubsettingEnabled(true);
        $options->setDefaultFont('Lora');
        $options->setDpi(150);
        $options->setFontDir(storage_path('app/dompdf-fonts'));
        $options->setFontCache(storage_path('app/dompdf-fonts'));
        $options->setTempDir(storage_path('app/dompdf-tmp'));
        foreach (['app/dompdf-fonts', 'app/dompdf-tmp'] as $dir) {
            if (! is_dir(storage_path($dir))) {
                mkdir(storage_path($dir), 0775, true);
            }
        }

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($doc, true, $specimen), 'UTF-8');
        $dompdf->setPaper('A4', in_array($doc->type->code, self::LANDSCAPE, true) ? 'landscape' : 'portrait');
        $dompdf->addInfo('Title', $doc->type->label.' — '.$doc->holder->full_name);
        $dompdf->addInfo('Author', config('evc.school'));
        $dompdf->addInfo('Subject', $doc->reference ? 'Document officiel n° '.$doc->reference : 'Aperçu de travail');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Produit et fige le PDF officiel au moment de la délivrance.
     * Le fichier n'est jamais régénéré après publication.
     */
    public function generate(OfficialDocument $doc, User $user): string
    {
        if ($doc->is_legacy) {
            throw new WorkflowException('Les documents historiques ne sont pas regénérés : leur PDF d’origine fait foi.');
        }
        if (! $doc->reference) {
            throw new WorkflowException('Le PDF officiel ne peut être généré qu’après l’attribution de la référence.');
        }
        if (in_array($doc->status, ['publie', 'revoque'], true)) {
            throw new WorkflowException('Le document est publié : son PDF officiel est figé.');
        }

        $bytes = $this->pdf($doc, false);
        $hash = hash('sha256', $bytes);
        $path = 'documents/'.$doc->reference.'-'.Str::lower(Str::random(8)).'.pdf';
        Storage::disk('local')->put($path, $bytes);

        $old = $doc->generated_pdf_path;
        $doc->update([
            'generated_pdf_path' => $path,
            'generated_sha256' => $hash,
            'generated_at' => now(),
            'template_version' => config('evc.template_version'),
            'pdf_path' => $path,
            'pdf_sha256' => $hash,
            'updated_by' => $user->id,
        ]);
        AuditLogger::log('generation', ($old ? 'PDF officiel regénéré' : 'PDF officiel généré').' (empreinte '.substr($hash, 0, 16).'…)', $doc, null,
            ['fichier' => $path, 'sha256' => $hash, 'gabarit' => config('evc.template_version'), 'ancien' => $old], $user->id);

        return $path;
    }

    /** Certificat publié du titulaire (cité dans la lettre de recommandation). */
    private function certificateOf(OfficialDocument $doc): ?string
    {
        return OfficialDocument::query()
            ->where('holder_id', $doc->holder_id)
            ->whereHas('type', fn ($q) => $q->where('is_certificate', true))
            ->where('status', 'publie')
            ->when($doc->formation, fn ($q) => $q->orderByRaw('formation = ? desc', [$doc->formation]))
            ->orderByDesc('issued_at')
            ->value('reference');
    }

    /** QR code en image (data URI), utilisable dans les pages d'administration. */
    public function qrDataUri(string $text, int $scale = 8): ?string
    {
        return $this->qr($text, $scale);
    }

    /** QR code en image PNG (GD) ou SVG, intégré directement dans le document. */
    private function qr(string $url, int $scale = 8): ?string
    {
        if (! class_exists(QRCode::class)) {
            return null;
        }
        $png = extension_loaded('gd');
        $options = new QROptions([
            'outputType' => $png ? QROutputInterface::GDIMAGE_PNG : QROutputInterface::MARKUP_SVG,
            'eccLevel' => EccLevel::M,
            'scale' => $scale,
            'addQuietzone' => false,
            'outputBase64' => true,
        ]);

        return (new QRCode($options))->render($url);
    }
}
