<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaskesFieldDefinition extends Model
{
    protected $table = 'faskes_field_definitions';

    protected $fillable = [
        'jenis_faskes',
        'label',
        'field_key',
        'type',
        'options',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
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

    public function scopeForJenis($query, ?string $jenisFaskes)
    {
        return $query->where(function ($q) use ($jenisFaskes) {
            $q->whereNull('jenis_faskes');
            if ($jenisFaskes) {
                $q->orWhere('jenis_faskes', $jenisFaskes);
            }
        });
    }
}
