<?php

namespace App\Support;

class ExcelSecurityHelper
{
    /**
     * Karakter berbahaya pemicu spreadsheet formula injection.
     */
    protected const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * Sanitasi teks agar aman dari CSV/Excel formula injection saat dibuka di Microsoft Excel / Google Sheets.
     * Jika string diawali dengan karakter pemicu formula, tambahkan single quote (') di awal.
     */
    public static function sanitizeCellValue(mixed $value): mixed
    {
        if ($value === null || ! is_string($value)) {
            return $value;
        }

        if (in_array($value[0], self::DANGEROUS_PREFIXES, true)) {
            return "'" . $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed !== '' && in_array($trimmed[0], self::DANGEROUS_PREFIXES, true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Sanitasi seluruh array nilai.
     */
    public static function sanitizeRow(array $row): array
    {
        return array_map([self::class, 'sanitizeCellValue'], $row);
    }
}
