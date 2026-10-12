<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaskesFieldDefinition extends Model
{
    protected $table = 'faskes_field_definitions';

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_NUMBER = 'number';
    public const TYPE_DATE = 'date';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_SELECT = 'select';
    public const TYPE_MULTISELECT = 'multiselect';
    public const TYPE_URL = 'url';
    public const TYPE_EMAIL = 'email';
    public const TYPE_PHONE = 'phone';

    public const VALID_TYPES = [
        self::TYPE_TEXT,
        self::TYPE_TEXTAREA,
        self::TYPE_NUMBER,
        self::TYPE_DATE,
        self::TYPE_BOOLEAN,
        self::TYPE_SELECT,
        self::TYPE_MULTISELECT,
        self::TYPE_URL,
        self::TYPE_EMAIL,
        self::TYPE_PHONE,
    ];

    protected $fillable = [
        'jenis_faskes',
        'label',
        'field_key',
        'type',
        'options',
        'config',
        'is_required',
        'sort_order',
        'is_active',
        'is_filterable',
        'show_in_list',
    ];

    protected $casts = [
        'options' => 'array',
        'config' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_filterable' => 'boolean',
        'show_in_list' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(FaskesFieldValue::class, 'field_definition_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeShowInList($query)
    {
        return $query->where('show_in_list', true);
    }

    public function scopeForJenis($query, ?string $jenisFaskes)
    {
        return $query->where(function ($q) use ($jenisFaskes) {
            $q->whereNull('jenis_faskes');
            if ($jenisFaskes) {
                $q->orWhere('jenis_faskes', $jenisFaskes);
            }
        });
    }

    /**
     * Mengambil nilai config dengan nilai default yang aman per tipe.
     */
    public function getConfigArray(): array
    {
        $cfg = is_array($this->config) ? $this->config : [];

        return match ($this->type) {
            self::TYPE_TEXT => array_merge([
                'max_length' => 255,
                'placeholder' => '',
                'help_text' => '',
            ], $cfg),
            self::TYPE_TEXTAREA => array_merge([
                'max_length' => 2000,
                'rows' => 3,
                'placeholder' => '',
                'help_text' => '',
            ], $cfg),
            self::TYPE_NUMBER => array_merge([
                'decimals' => 0,
                'min' => null,
                'max' => null,
                'unit' => '',
            ], $cfg),
            self::TYPE_MULTISELECT => array_merge([
                'max_select' => null,
            ], $cfg),
            default => $cfg,
        };
    }
}
