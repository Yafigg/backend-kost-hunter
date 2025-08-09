<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'kos_id',
        'user_id',
        'comment',
        'rating'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    public function kos()
    {
        return $this->belongsTo(Kos::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reply()
    {
        return $this->hasOne(ReviewReply::class);
    }
}