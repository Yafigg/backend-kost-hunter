<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KosFacility extends Model
{
    use HasFactory;

    protected $fillable = [
        'kos_id',
        'facility',
        'icon'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class);
    }
}