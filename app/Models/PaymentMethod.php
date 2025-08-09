<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'kos_id',
        'bank_name',
        'account_number',
        'account_name',
        'type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}