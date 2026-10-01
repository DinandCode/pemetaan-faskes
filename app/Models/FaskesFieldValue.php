<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaskesFieldValue extends Model
{
    protected $table = 'faskes_field_values';

    protected $fillable = [
        'faskes_id',
        'field_definition_id',
        'value',
    ];

    public function faskes(): BelongsTo
    {
        return $this->belongsTo(Faskes::class, 'faskes_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(FaskesFieldDefinition::class, 'field_definition_id');
    }
}
