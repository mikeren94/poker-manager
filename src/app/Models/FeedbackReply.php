<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackReply extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackReplyFactory> */
    use HasFactory;

    protected $fillable = [
        'feedback_id',
        'user_id',
        'message',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
