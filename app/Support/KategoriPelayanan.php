<?php

namespace App\Support;

class KategoriPelayanan
{
    public const RAWAT_INAP = 'rawat_inap';
    public const RAWAT_JALAN = 'rawat_jalan';

    public const LABEL_RAWAT_INAP = 'Rawat Inap';
    public const LABEL_NON_RAWAT_INAP = 'Non Rawat Inap';

    /**
     * Mengembalikan mapping nilai internal => label tampilan.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::RAWAT_INAP => self::LABEL_RAWAT_INAP,
            self::RAWAT_JALAN => self::LABEL_NON_RAWAT_INAP,
        ];
    }

    /**
     * Mengembalikan daftar opsi label untuk validasi template import atau petunjuk.
     *
     * @return array<int, string>
     */
    public static function importOptions(): array
    {
        return [
            self::LABEL_RAWAT_INAP,
            self::LABEL_NON_RAWAT_INAP,
        ];
    }

    /**
     * Mengembalikan label tampilan berdasarkan nilai internal di DB.
     *
     * @param string|null $value
     * @return string
     */
    public static function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $normalized = strtolower(trim($value));

        if ($normalized === self::RAWAT_INAP || $normalized === 'rawat inap') {
            return self::LABEL_RAWAT_INAP;
        }

        if (
            $normalized === self::RAWAT_JALAN ||
            $normalized === 'rawat jalan' ||
            $normalized === 'non rawat inap' ||
            $normalized === 'non_rawat_inap' ||
            $normalized === 'non-rawat-inap'
        ) {
            return self::LABEL_NON_RAWAT_INAP;
        }

        // Fallback jika ada variasi lain
        return ucwords(str_replace('_', ' ', $value));
    }

    /**
     * Menormalisasi input varian (misal dari form atau file excel import)
     * ke nilai internal database ('rawat_inap' atau 'rawat_jalan').
     *
     * Menerima case-insensitive:
     * - "Rawat Inap", "rawat_inap", "rawat-inap", "inap" -> 'rawat_inap'
     * - "Non Rawat Inap", "non_rawat_inap", "non-rawat-inap", "Rawat Jalan", "rawat_jalan", "rawat-jalan", "jalan" -> 'rawat_jalan'
     *
     * @param string|null $input
     * @param string $default
     * @return string
     */
    public static function normalize(?string $input, string $default = self::RAWAT_JALAN): string
    {
        if ($input === null) {
            return $default;
        }

        $clean = strtolower(trim($input));
        $clean = str_replace(['-', '_'], ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        if ($clean === 'rawat inap' || $clean === 'inap') {
            return self::RAWAT_INAP;
        }

        if (
            $clean === 'non rawat inap' ||
            $clean === 'rawat jalan' ||
            $clean === 'jalan' ||
            $clean === 'non inap'
        ) {
            return self::RAWAT_JALAN;
        }

        return $default;
    }
}
