<?php

namespace App\Support;

class Reference
{
    /** Formats acceptés : EVC-DG-2026-0007, EVC-ATT-2026-0001 et anciens ATT-EVC-2026-0001. */
    public const PATTERN = '/^[A-Z]{3}-[A-Z]{2,5}-\d{4}-\d{4}$/';

    /** Majuscules ; espaces, tirets typographiques, « _ », « / » et « . » convertis en tirets. */
    public static function normalize(?string $value): string
    {
        $value = mb_strtoupper(trim((string) $value));
        $value = preg_replace('/[\x{2010}-\x{2015}\x{2212}_\/\.]/u', '-', $value);
        $value = preg_replace('/\s+/u', '-', $value);
        $value = preg_replace('/[^A-Z0-9-]/', '', $value);

        return trim(preg_replace('/-+/', '-', $value), '-');
    }

    public static function isValid(string $reference): bool
    {
        return (bool) preg_match(self::PATTERN, $reference);
    }
}
