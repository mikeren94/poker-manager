<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['vpip'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function getVpipAttribute(): float
    {
        return $this->getVpip();
    }

    private function getVpip() 
    {
        $playerIds = $this->players()->pluck('id');
        $allActions = HandAction::whereIn('player_id', $playerIds)->get();
        $vpipActions = $allActions->filter(function ($action) {
            return $action->street === 0 && in_array($action->action, ['call', 'raise', '3bet']);
        });
        $vpipHandCount = $vpipActions->pluck('hand_id')->unique()->count();
        $totalHandCount = $allActions->pluck('hand_id')->unique()->count();
        return $totalHandCount > 0 ? round(($vpipHandCount / $totalHandCount) * 100) : 0;
    }   
}
