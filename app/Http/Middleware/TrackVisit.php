<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Enregistre les consultations du registre public (mesure d'audience sans cookie).
 * Les robots, les requêtes de préchargement et les pages d'administration sont ignorés.
 * Aucune adresse IP ni jeton QR n'est conservé.
 */
class TrackVisit
{
    private const ROUTES = [
        'public.index' => 'accueil',
        'public.show' => 'verification',
        'public.qr' => 'qr',
        'public.pdf' => 'pdf',
        'public.privacy' => 'confidentialite',
    ];

    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|curl|wget|python|httpclient|java\/|go-http|headless|lighthouse|monitor|uptime|scan/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $page = self::ROUTES[$request->route()?->getName()] ?? null;
            $ua = (string) $request->userAgent();

            if ($page && $request->isMethod('GET') && $ua !== '' && ! preg_match(self::BOTS, $ua)
                && ! in_array(strtolower((string) $request->header('Purpose', $request->header('Sec-Purpose', ''))), ['prefetch', 'prerender'], true)
                && ! $request->user()) { // les consultations de l'équipe EVC connectée ne comptent pas
                SiteVisit::create([
                    'page' => $page,
                    'visitor_hash' => hash('sha256', $request->ip().'|'.$ua.'|'.config('app.key')),
                    'device' => self::device($ua),
                    'browser' => self::browser($ua),
                    'os' => self::os($ua),
                    'lang' => self::lang((string) $request->header('Accept-Language')),
                    'referrer' => self::referrer($request),
                    'status' => $response->getStatusCode(),
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Mesure d’audience indisponible', ['error' => $e->getMessage()]);
        }

        return $response;
    }

    public static function device(string $ua): string
    {
        if (preg_match('/ipad|tablet|kindle|silk|(android(?!.*mobile))/i', $ua)) {
            return 'tablette';
        }

        return preg_match('/mobi|iphone|ipod|android|opera mini|iemobile/i', $ua) ? 'mobile' : 'ordinateur';
    }

    public static function browser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/edg(e|a|ios)?\//i', $ua) => 'Edge',
            (bool) preg_match('/opr\/|opera/i', $ua) => 'Opera',
            (bool) preg_match('/samsungbrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/ucbrowser/i', $ua) => 'UC Browser',
            (bool) preg_match('/firefox|fxios/i', $ua) => 'Firefox',
            (bool) preg_match('/chrome|crios|chromium/i', $ua) => 'Chrome',
            (bool) preg_match('/safari/i', $ua) => 'Safari',
            default => 'Autre',
        };
    }

    public static function os(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/android/i', $ua) => 'Android',
            (bool) preg_match('/iphone|ipad|ipod/i', $ua) => 'iOS',
            (bool) preg_match('/windows/i', $ua) => 'Windows',
            (bool) preg_match('/mac os x|macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/linux|cros/i', $ua) => 'Linux',
            default => 'Autre',
        };
    }

    public static function lang(string $header): ?string
    {
        return preg_match('/^\s*([a-z]{2})/i', $header, $m) ? strtolower($m[1]) : null;
    }

    public static function referrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (! $host) {
            return null; // accès direct (lien saisi, favori, QR code, application)
        }
        if (strcasecmp($host, $request->getHost()) === 0) {
            return '(interne)';
        }

        return mb_substr(preg_replace('/^(www\.|m\.|l\.|lm\.)/i', '', strtolower($host)), 0, 120);
    }
}
