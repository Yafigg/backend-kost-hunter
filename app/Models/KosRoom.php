<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KosRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'kos_id',
        'room_number',
        'is_available',
        'room_type'
    ];

    protected $casts = [
        'is_available' => 'boolean'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'room_id');
    }
}