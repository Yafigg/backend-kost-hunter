<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'owner_reply'
    ];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}