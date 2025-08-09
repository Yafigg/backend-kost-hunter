<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KosImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'kos_id',
        'file',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class);
    }
}
