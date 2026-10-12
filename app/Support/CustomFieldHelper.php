<?php

namespace App\Support;

use App\Models\FaskesFieldDefinition;
use Carbon\Carbon;

class CustomFieldHelper
{
    /**
     * Memvalidasi dan menormalisasi nilai kolom tambahan (custom field)
     * untuk seluruh 10 tipe data, baik saat disimpan melalui form maupun import Excel.
     *
     * @return array [
     *     'error' => string|null,
     *     'is_empty' => bool,
     *     'value' => string|null,
     *     'value_number' => float|null,
     *     'value_date' => string|null,
     *     'value_bool' => bool|null,
     *     'value_json' => array|null,
     * ]
     */
    public static function processValue(FaskesFieldDefinition $def, mixed $rawVal, ?int $faskesId = null): array
    {
        $result = [
            'error'        => null,
            'is_empty'     => false,
            'value'        => null,
            'value_number' => null,
            'value_date'   => null,
            'value_bool'   => null,
            'value_json'   => null,
        ];

        $type = $def->type;
        $cfg = $def->getConfigArray();

        $isEmpty = ($rawVal === null || (is_string($rawVal) && trim($rawVal) === '') || (is_array($rawVal) && empty($rawVal)));

        if ($def->is_required && $isEmpty) {
            $result['error'] = "Kolom '{$def->label}' wajib diisi.";
            return $result;
        }

        if ($isEmpty) {
            $result['is_empty'] = true;
            return $result;
        }

        switch ($type) {
            case FaskesFieldDefinition::TYPE_TEXT:
                $strVal = trim((string) $rawVal);
                $maxLen = (int) ($cfg['max_length'] ?? 255);
                if (mb_strlen($strVal) > $maxLen) {
                    $result['error'] = "Kolom '{$def->label}' maksimal {$maxLen} karakter.";
                    return $result;
                }
                $result['value'] = $strVal;
                break;

            case FaskesFieldDefinition::TYPE_TEXTAREA:
                $strVal = trim((string) $rawVal);
                $maxLen = (int) ($cfg['max_length'] ?? 2000);
                if (mb_strlen($strVal) > $maxLen) {
                    $result['error'] = "Kolom '{$def->label}' maksimal {$maxLen} karakter.";
                    return $result;
                }
                $result['value'] = $strVal;
                break;

            case FaskesFieldDefinition::TYPE_NUMBER:
                $numStr = str_replace(',', '.', trim((string) $rawVal));
                if (! is_numeric($numStr)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa angka valid.";
                    return $result;
                }
                $floatVal = (float) $numStr;
                if (isset($cfg['min']) && $cfg['min'] !== null && $floatVal < (float) $cfg['min']) {
                    $result['error'] = "Kolom '{$def->label}' minimal {$cfg['min']}.";
                    return $result;
                }
                if (isset($cfg['max']) && $cfg['max'] !== null && $floatVal > (float) $cfg['max']) {
                    $result['error'] = "Kolom '{$def->label}' maksimal {$cfg['max']}.";
                    return $result;
                }
                $decimals = (int) ($cfg['decimals'] ?? 0);
                $decimals = max(0, min(4, $decimals));
                $result['value'] = number_format($floatVal, $decimals, '.', '');
                $result['value_number'] = $floatVal;
                break;

            case FaskesFieldDefinition::TYPE_DATE:
                // Dukung DateTime object, angka serial Excel, atau format tanggal string
                $dateStr = null;
                if ($rawVal instanceof \DateTimeInterface) {
                    $dateStr = $rawVal->format('Y-m-d');
                } elseif (is_numeric($rawVal) && (float) $rawVal > 1000) {
                    try {
                        $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $rawVal);
                        $dateStr = $dt->format('Y-m-d');
                    } catch (\Throwable $e) {}
                }

                if (! $dateStr) {
                    $strVal = trim((string) $rawVal);
                    try {
                        $parsedDate = Carbon::parse($strVal);
                        $dateStr = $parsedDate->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $result['error'] = "Format tanggal pada kolom '{$def->label}' tidak valid (gunakan YYYY-MM-DD).";
                        return $result;
                    }
                }

                $result['value'] = $dateStr;
                $result['value_date'] = $dateStr;
                break;

            case FaskesFieldDefinition::TYPE_BOOLEAN:
                $boolStr = strtolower(trim((string) $rawVal));
                if (in_array($boolStr, ['1', 'true', 'ya', 'yes', 'y'], true)) {
                    $result['value'] = 'Ya';
                    $result['value_bool'] = true;
                } elseif (in_array($boolStr, ['0', 'false', 'tidak', 'no', 't'], true)) {
                    $result['value'] = 'Tidak';
                    $result['value_bool'] = false;
                } else {
                    $result['error'] = "Kolom '{$def->label}' harus bernilai 'Ya' atau 'Tidak'.";
                    return $result;
                }
                break;

            case FaskesFieldDefinition::TYPE_SELECT:
                $strVal = trim((string) $rawVal);
                $allowedOptions = (array) ($def->options ?? []);
                if ($faskesId) {
                    $existingVal = \App\Models\FaskesFieldValue::where('faskes_id', $faskesId)
                        ->where('field_definition_id', $def->id)
                        ->value('value');
                    if ($existingVal !== null && $existingVal !== '') {
                        $allowedOptions[] = $existingVal;
                    }
                }

                $matched = null;
                foreach ($allowedOptions as $opt) {
                    if (strcasecmp($strVal, (string) $opt) === 0) {
                        $matched = $opt;
                        break;
                    }
                }

                if ($matched === null) {
                    $result['error'] = "Pilihan '{$strVal}' untuk kolom '{$def->label}' tidak valid. Pilihan: " . implode(', ', $allowedOptions);
                    return $result;
                }
                $result['value'] = $matched;
                break;

            case FaskesFieldDefinition::TYPE_MULTISELECT:
                $selectedArray = is_array($rawVal) ? $rawVal : array_map('trim', explode(';', (string) $rawVal));
                $selectedArray = array_values(array_unique(array_filter($selectedArray, fn ($o) => trim((string) $o) !== '')));

                if (empty($selectedArray)) {
                    if ($def->is_required) {
                        $result['error'] = "Kolom '{$def->label}' wajib diisi.";
                        return $result;
                    }
                    $result['is_empty'] = true;
                    return $result;
                }

                $maxSelect = isset($cfg['max_select']) ? (int) $cfg['max_select'] : null;
                if ($maxSelect && count($selectedArray) > $maxSelect) {
                    $result['error'] = "Maksimal {$maxSelect} pilihan untuk kolom '{$def->label}'.";
                    return $result;
                }

                $allowedOptions = (array) ($def->options ?? []);
                if ($faskesId) {
                    $existingValues = \App\Models\FaskesFieldValue::where('faskes_id', $faskesId)
                        ->where('field_definition_id', $def->id)
                        ->value('value_json');
                    if (is_array($existingValues)) {
                        $allowedOptions = array_merge($allowedOptions, $existingValues);
                    }
                }

                $normalizedSelected = [];
                foreach ($selectedArray as $item) {
                    $matchedItem = null;
                    foreach ($allowedOptions as $opt) {
                        if (strcasecmp($item, (string) $opt) === 0) {
                            $matchedItem = $opt;
                            break;
                        }
                    }
                    if ($matchedItem === null) {
                        $result['error'] = "Pilihan '{$item}' pada kolom '{$def->label}' tidak valid.";
                        return $result;
                    }
                    $normalizedSelected[] = $matchedItem;
                }

                $result['value'] = implode('; ', $normalizedSelected);
                $result['value_json'] = $normalizedSelected;
                break;

            case FaskesFieldDefinition::TYPE_URL:
                $urlVal = trim((string) $rawVal);
                if (! filter_var($urlVal, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $urlVal)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa tautan URL valid (awali dengan http:// atau https://).";
                    return $result;
                }
                $result['value'] = $urlVal;
                break;

            case FaskesFieldDefinition::TYPE_EMAIL:
                $emailVal = trim((string) $rawVal);
                if (! filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa alamat email yang valid.";
                    return $result;
                }
                $result['value'] = $emailVal;
                break;

            case FaskesFieldDefinition::TYPE_PHONE:
                $phoneVal = trim((string) $rawVal);
                if (! preg_match('/^[0-9+()\-\s]{6,25}$/', $phoneVal)) {
                    $result['error'] = "Kolom '{$def->label}' harus berupa nomor telepon yang valid (6-25 digit/tanda + - ()).";
                    return $result;
                }
                $result['value'] = $phoneVal;
                break;

            default:
                $result['value'] = trim((string) $rawVal);
                break;
        }

        return $result;
    }
}
