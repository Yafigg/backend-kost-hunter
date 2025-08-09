<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'kos_id',
        'room_id',
        'user_id',
        'booking_code',
        'start_date',
        'end_date',
        'total_price',
        'status',
        'rejected_reason'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_price' => 'integer'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class);
    }

    public function room()
    {
        return $this->belongsTo(KosRoom::class, 'room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Generate booking code
    public static function generateBookingCode()
    {
        $prefix = 'KH-' . date('Y') . '-';
        $lastBooking = self::where('booking_code', 'like', $prefix . '%')
            ->orderBy('booking_code', 'desc')
            ->first();

        if ($lastBooking) {
            $lastNumber = intval(substr($lastBooking->booking_code, -3));
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $newNumber;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accept');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'reject');
    }
}