<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'game_sessions';
    /** @use HasFactory<\Database\Factories\SessionFactory> */
    use HasFactory;
}
