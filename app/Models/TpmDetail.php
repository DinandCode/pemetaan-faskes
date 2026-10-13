<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detail SIP untuk semua jenis Tempat Praktik Mandiri (TPMD, TPMDG, TPMB, TPMP).
 */
class TpmDetail extends Model
{
    protected $table = 'tpm_details';

    protected $fillable = [
        'faskes_id',
        'masa_sip',
    ];

    protected $casts = [
        'masa_sip' => 'date',
    ];

    public function faskes(): BelongsTo
    {
        return $this->belongsTo(Faskes::class);
    }
}
