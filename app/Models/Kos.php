<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kos extends Model
{
    use HasFactory;

    protected $table = 'kos';

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'description',
        'price_per_month',
        'gender',
        'latitude',
        'longitude',
        'whatsapp_number',
        'is_active',
        'view_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'price_per_month' => 'integer',
        'view_count' => 'integer'
    ];

    // Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rooms()
    {
        return $this->hasMany(KosRoom::class);
    }

    public function images()
    {
        return $this->hasMany(KosImage::class);
    }

    public function facilities()
    {
        return $this->hasMany(KosFacility::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGender($query, $gender)
    {
        if ($gender === 'all') {
            return $query;
        }
        return $query->whereIn('gender', [$gender, 'all']);
    }

    // Methods
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function getAvailableRoomsCount()
    {
        return $this->rooms()->where('is_available', true)->count();
    }

    public function getPrimaryImage()
    {
        return $this->images()->where('is_primary', true)->first() 
            ?? $this->images()->first();
    }
}