<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaskesFieldValue extends Model
{
    protected $table = 'faskes_field_values';

    protected $fillable = [
        'faskes_id',
        'field_definition_id',
        'value',
        'value_number',
        'value_date',
        'value_bool',
        'value_json',
    ];

    protected $casts = [
        'value_number' => 'float',
        'value_date' => 'date',
        'value_bool' => 'boolean',
        'value_json' => 'array',
    ];

    protected $appends = [
        'formatted_value',
    ];

    public function faskes(): BelongsTo
    {
        return $this->belongsTo(Faskes::class, 'faskes_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(FaskesFieldDefinition::class, 'field_definition_id');
    }

    /**
     * Mengembalikan teks tampilan berformat sesuai tipe data definisinya.
     */
    public function getFormattedValueAttribute(): string
    {
        $def = $this->definition;
        if (! $def) {
            return (string) $this->value;
        }

        $type = $def->type;
        $cfg = $def->getConfigArray();

        switch ($type) {
            case FaskesFieldDefinition::TYPE_NUMBER:
                if ($this->value_number !== null || ($this->value !== null && is_numeric($this->value))) {
                    $num = (float) ($this->value_number ?? $this->value);
                    $decimals = (int) ($cfg['decimals'] ?? 0);
                    $decimals = max(0, min(4, $decimals));
                    $formatted = number_format($num, $decimals, ',', '.');
                    $unit = trim((string) ($cfg['unit'] ?? ''));
                    return $unit !== '' ? "{$formatted} {$unit}" : $formatted;
                }
                return (string) ($this->value ?? '');

            case FaskesFieldDefinition::TYPE_DATE:
                $rawDate = $this->value_date ? $this->value_date->format('Y-m-d') : $this->value;
                if ($rawDate) {
                    try {
                        return Carbon::parse($rawDate)->locale('id')->translatedFormat('d F Y');
                    } catch (\Throwable $e) {
                        return (string) $rawDate;
                    }
                }
                return '';

            case FaskesFieldDefinition::TYPE_BOOLEAN:
                if ($this->value_bool !== null) {
                    return $this->value_bool ? 'Ya' : 'Tidak';
                }
                if ($this->value !== null && $this->value !== '') {
                    $lower = strtolower($this->value);
                    return in_array($lower, ['1', 'true', 'ya', 'yes'], true) ? 'Ya' : 'Tidak';
                }
                return '';

            case FaskesFieldDefinition::TYPE_MULTISELECT:
                $items = $this->value_json;
                if (! is_array($items)) {
                    $items = array_filter(array_map('trim', explode(';', (string) $this->value)));
                }
                return implode(', ', $items);

            default:
                return (string) ($this->value ?? '');
        }
    }
}
