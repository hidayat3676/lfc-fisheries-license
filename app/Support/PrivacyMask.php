<?php

namespace App\Support;

class PrivacyMask
{
    public static function name(?string $name, string $mode = 'masked'): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '—';
        }

        if ($mode === 'full') {
            return $name;
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $masked = [];
        foreach ($parts as $part) {
            $len = mb_strlen($part);
            if ($len <= 1) {
                $masked[] = $part;
            } elseif ($len === 2) {
                $masked[] = mb_substr($part, 0, 1).'*';
            } else {
                $masked[] = mb_substr($part, 0, 1).str_repeat('*', $len - 2).mb_substr($part, -1);
            }
        }

        return implode(' ', $masked);
    }

    public static function cnic(?string $cnic, string $mode = 'masked'): ?string
    {
        $cnic = trim((string) $cnic);
        if ($cnic === '') {
            return null;
        }

        if ($mode === 'full') {
            return $cnic;
        }

        // #####-#######-# → *****‑****123‑*
        $digits = preg_replace('/\D+/', '', $cnic) ?? '';
        if (strlen($digits) < 5) {
            return '*****';
        }

        $visible = substr($digits, -4);
        $maskedDigits = str_repeat('*', max(0, strlen($digits) - 4)).$visible;

        if (strlen($maskedDigits) === 13) {
            return substr($maskedDigits, 0, 5).'-'.substr($maskedDigits, 5, 7).'-'.substr($maskedDigits, 12, 1);
        }

        return $maskedDigits;
    }
}
