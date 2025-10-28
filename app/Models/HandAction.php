<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandAction extends Model
{
    /** @use HasFactory<\Database\Factories\HandActionFactory> */
    use HasFactory;

    protected $fillable = [
        'hand_id',
        'player_id',
        'action',
        'amount',
        'street',
        'action_order'
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
