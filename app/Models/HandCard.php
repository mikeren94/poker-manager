<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HandCard extends Model
{
    /** @use HasFactory<\Database\Factories\HandCardFactory> */
    use HasFactory;

    protected $fillable = [
        'context',
        'card_id',
        'hand_id',
        'player_id'
    ];
}
