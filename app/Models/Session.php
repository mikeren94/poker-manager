<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'game_sessions';
    /** @use HasFactory<\Database\Factories\SessionFactory> */
    use HasFactory;

    protected $fillable = [
        'player_id',
        'site_id',
        'session_id',
        'type',
        'stakes',
        'start_time',
        'end_time',
        'buy_in',
        'cash_out',
        'net_profit'
    ];

    protected $appends = ['result'];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function hands()
    {
        return $this->hasMany(Hand::class, 'game_session_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function getResultAttribute()
    {
        return HandPlayer::whereHas('hand', function ($query) {
            $query->where('game_session_id', $this->id); // assuming you're inside a Session model
        })
        ->where('player_id', $this->player_id)
        ->where('result', '!=', 0)
        ->sum('result');
    }
}
