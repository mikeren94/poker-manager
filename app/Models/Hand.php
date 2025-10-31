<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hand extends Model
{
    /** @use HasFactory<\Database\Factories\HandFactory> */
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'hand_number',
        'timestamp',
        'pot_size',
        'rake',
        'showdown'
    ];

    public function session()
    {
        return $this->belongsTo(Session::class, 'game_session_id');
    }

    public function hand_players()
    {
        return $this->hasMany(HandPlayer::class);
    }
}
