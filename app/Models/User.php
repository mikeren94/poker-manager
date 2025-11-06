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

    protected $appends = [
        'vpip', 
        'rakePaid', 
        'winRate', 
        'totalProfit', 
        'playerIds',
        'showdownsWonPercent'
    ];

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

    public function getWinRateAttribute(): string
    {
        return $this->getWinRate();
    }

    public function getRakePaidAttribute(): float
    {
        return $this->getRakePaid();
    }

    public function getTotalProfitAttribute(): float
    {
        return $this->getTotalProfit();
    }
    
    public function getPlayerIdsAttribute()
    {
        return $this->players()->pluck('id');
    }

    public function getShowdownsWonPercentAttribute()
    {
        return $this->getShowdownPercent();
    }

    private function getShowdownPercent()
    {
        $showdownsReached = HandPlayer::whereIn('player_id', $this->playerIds)
            ->where('showdown', true)
            ->pluck('hand_id')
            ->unique()
            ->count();

        $showdownsWon = HandPlayer::whereIn('player_id', $this->playerIds)
            ->where('showdown', true)
            ->where('result', '>', 0)
            ->pluck('hand_id')
            ->unique()
            ->count();
        
        $showdownWinPercent = $showdownsReached > 0
            ? ($showdownsWon / $showdownsReached) * 100
            : 0;

        return round($showdownWinPercent,2);
    } 

    private function getTotalProfit()
    {
        return HandPlayer::whereIn('player_id', $this->playerIds)->sum('result');
    }

    private function getVpip() 
    {
        $allActions = HandAction::whereIn('player_id', $this->playerIds)->get();
        $vpipActions = $allActions->filter(function ($action) {
            return $action->street === 0 && in_array($action->action, ['call', 'raise', '3bet']);
        });
        $vpipHandCount = $vpipActions->pluck('hand_id')->unique()->count();
        $totalHandCount = $allActions->pluck('hand_id')->unique()->count();
        return $totalHandCount > 0 ? round(($vpipHandCount / $totalHandCount) * 100) : 0;
    }   

    private function getRakePaid()
    {
        $rakePaid = 0;

        $handIds = HandPlayer::whereIn('player_id', $this->playerIds)
            ->pluck('hand_id')
            ->unique();

        foreach ($handIds as $handId) {
            $hand = Hand::find($handId);
            $handRake = $hand->rake;

            $winners = HandPlayer::where('hand_id', $handId)
                ->where('result', '>', 0)
                ->get();

            $totalWin = $winners->sum('result');

            foreach ($winners as $winner) {
                if (in_array($winner->player_id, $this->playerIds->toArray())) {
                    $share = $winner->result / $totalWin;
                    $rakePaid += $handRake * $share;
                }
            }
        }

        return round($rakePaid, 2);
    }

    private function getWinRate()
    {
        $handIds = HandPlayer::whereIn('player_id', $this->playerIds)
            ->where('result', '!=', 0)
            ->get();

        $totalBBs = 0;
        $totalHands = $handIds->count();
        $totalProfit = $this->totalProfit;
        foreach ($handIds as $handPlayer)
        {
            $bb = $handPlayer->hand->bb_size;
            $totalBBs += $bb;
        }

        $bbPer100 = $totalHands > 0 ? ($totalProfit / $totalBBs) * 100 : 0;
        return round($bbPer100,2);
    }
}
