<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GriyaSehatDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'griya_sehat_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'faskes_id',
        'masa_izin',
        'pj',
        'kontak_pj',
        'jumlah_sdm',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'masa_izin'   => 'date',
            'jumlah_sdm'  => 'integer',
        ];
    }

    /**
     * Relasi belongsTo ke Faskes.
     */
    public function faskes(): BelongsTo
    {
        return $this->belongsTo(Faskes::class, 'faskes_id');
    }
}
