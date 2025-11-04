<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandPlayer extends Model
{
    /** @use HasFactory<\Database\Factories\HandPlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'hand_id',
        'player_id',
        'position',
        'action',
        'result',
        'is_hero',
        'is_winner'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
    
    public function hand()
    {
        return $this->belongsTo(Hand::class);
    }
}
