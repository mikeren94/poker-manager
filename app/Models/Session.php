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

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function hands()
    {
        return $this->hasMany(Hand::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
