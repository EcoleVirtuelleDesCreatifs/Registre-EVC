<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Textes officiels en français : nombres et dates en toutes lettres, accords,
 * textes types. Miroir exact de docgen.js (maquette) pour un rendu identique.
 */
class FrenchText
{
    private const UNITS = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze',
        'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];

    private const TENS = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    public const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    public static function number(int $n): string
    {
        if ($n < 20) {
            return self::UNITS[$n];
        }
        if ($n < 70) {
            $t = intdiv($n, 10);
            $u = $n % 10;

            return self::TENS[$t].($u === 0 ? '' : ($u === 1 ? ' et un' : '-'.self::UNITS[$u]));
        }
        if ($n < 80) {
            return $n === 71 ? 'soixante et onze' : 'soixante-'.self::UNITS[$n - 60];
        }
        if ($n < 100) {
            return $n === 80 ? 'quatre-vingts' : 'quatre-vingt-'.self::UNITS[$n - 80];
        }
        if ($n < 1000) {
            $c = intdiv($n, 100);
            $r = $n % 100;

            return ($c === 1 ? 'cent' : self::UNITS[$c].' cent'.($r === 0 ? 's' : '')).($r ? ' '.self::number($r) : '');
        }
        $m = intdiv($n, 1000);
        $r = $n % 1000;

        return ($m === 1 ? 'mille' : self::number($m).' mille').($r ? ' '.self::number($r) : '');
    }

    public static function ordinal(int $n): string
    {
        if ($n === 1) {
            return 'premier';
        }
        $w = self::number($n);
        if (str_ends_with($w, 'cinq')) {
            return $w.'uième';
        }
        if (str_ends_with($w, 'neuf')) {
            return mb_substr($w, 0, -1).'vième';
        }
        if (str_ends_with($w, 'e')) {
            $w = mb_substr($w, 0, -1);
        }

        return $w.'ième';
    }

    public static function ucfirst(string $s): string
    {
        return mb_strtoupper(mb_substr($s, 0, 1)).mb_substr($s, 1);
    }

    /** « 13 novembre 2026 », « 1er mars 2026 » */
    public static function date($date): string
    {
        if (blank($date)) {
            return '…';
        }
        $d = Carbon::parse($date);

        return ($d->day === 1 ? '1er' : $d->day).' '.self::MONTHS[$d->month - 1].' '.$d->year;
    }

    /** « le treizième jour du mois de novembre de l’an deux mille vingt-six » */
    public static function solemnDate($date): string
    {
        $d = Carbon::parse($date);

        return 'le '.self::ordinal($d->day).' jour du mois de '.self::MONTHS[$d->month - 1].' de l’an '.self::number($d->year);
    }

    /** « Quatre (4) mois » ou « Vingt (20) jours » */
    public static function duration($start, $end): string
    {
        if (blank($start) || blank($end)) {
            return '';
        }
        $a = Carbon::parse($start);
        $b = Carbon::parse($end);
        $diffDays = $b->day - $a->day;
        $months = ($b->year - $a->year) * 12 + ($b->month - $a->month) + ($diffDays >= 15 ? 1 : ($diffDays <= -15 ? -1 : 0));
        if ($months < 1) {
            $days = (int) $a->diffInDays($b) + 1;

            return self::ucfirst(self::number($days))." ({$days}) jours";
        }

        return self::ucfirst(self::number($months))." ({$months}) mois";
    }

    /** Accords selon la civilité (M. / Mme). */
    public static function agreements(?string $civility): array
    {
        $f = $civility === 'Mme';

        return [
            'f' => $f, 'civ' => $f ? 'Madame' : 'Monsieur', 'e' => $f ? 'e' : '', 'il' => $f ? 'elle' : 'il', 'Il' => $f ? 'Elle' : 'Il',
            'Le' => $f ? 'La' : 'Le', 'interesse' => $f ? 'l’intéressée' : 'l’intéressé',
        ];
    }

    public static function signatory(?string $key): array
    {
        $all = config('evc.signataires');

        return $all[$key ?: 'dg'] ?? $all['dg'];
    }

    public static function defaultAppreciation(?string $civility): string
    {
        return 'Durant cette période, '.self::agreements($civility)['interesse'].' a fait preuve de sérieux, d’assiduité et d’engagement dans l’exécution des tâches qui lui ont été confiées.';
    }

    /** Texte type de la lettre de recommandation (paragraphes séparés par une ligne vide). */
    public static function recommendation(string $name, ?string $civility, ?string $signatory, ?string $formationLabel, ?string $certificate): string
    {
        $a = self::agreements($civility);
        $s = self::signatory($signatory);
        $parts = preg_split('/\s+/', trim($name));
        $last = end($parts);
        $form = $formationLabel ? ' en '.$formationLabel : '';
        $cert = $certificate ? ', et titulaire du Certificat Professionnel de l’EVC dans cette spécialité (certificat n° '.$certificate.')' : '';

        return implode("\n\n", [
            "C’est avec plaisir et en toute confiance que je recommande {$a['civ']} {$name}, formé{$a['e']} à l’École Virtuelle des Créatifs (EVC){$form}{$cert}.",
            "En ma qualité de {$s['title']}, j’ai pu suivre son parcours et apprécier la qualité de son travail. Au cours de sa formation, {$a['il']} a démontré de solides compétences, une réelle maîtrise des outils professionnels et une grande capacité à mener ses projets avec rigueur et créativité.",
            "{$a['civ']} {$last} s’est distingué{$a['e']} par son sérieux, son assiduité et son engagement. Fiable et organisé{$a['e']}, {$a['il']} respecte les délais, s’intègre facilement dans une équipe et sait faire preuve d’initiative.",
            "Pour l’ensemble de ces raisons, je recommande {$a['civ']} {$name} sans réserve pour tout poste, stage ou mission dans son domaine de compétence. Je suis convaincu".($s['feminine'] ? 'e' : '')." qu’{$a['il']} saura apporter une contribution de qualité à toute structure qui lui accordera sa confiance.",
            'Je reste à votre entière disposition pour tout renseignement complémentaire.',
        ]);
    }

    /** « Titre : description » par ligne → [['t' => …, 'd' => …], …] */
    public static function missions(?string $text): array
    {
        $out = [];
        foreach (preg_split('/\R+/u', (string) $text) as $line) {
            $line = trim(preg_replace('/^[-•*\d.)\s]+/u', '', trim($line)));
            if ($line === '') {
                continue;
            }
            $i = mb_strpos($line, ':');
            $out[] = $i > 0 ? ['t' => trim(mb_substr($line, 0, $i)), 'd' => trim(mb_substr($line, $i + 1))] : ['t' => '', 'd' => $line];
        }

        return $out;
    }

    /** Taille du nom selon sa longueur, pour qu'il tienne sur une ligne. */
    public static function nameSize(string $name, array $sizes): string
    {
        $n = mb_strlen($name);

        return $n > 34 ? $sizes[2] : ($n > 26 ? $sizes[1] : $sizes[0]);
    }

    /** Paragraphes d'un texte libre. */
    public static function paragraphs(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/u', $text))));
    }
}
