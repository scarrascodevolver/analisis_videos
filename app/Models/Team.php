<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'is_own_team',
    ];

    protected function casts(): array
    {
        return [
            'is_own_team' => 'boolean',
        ];
    }

    public function analyzedVideos()
    {
        return $this->hasMany(Video::class, 'analyzed_team_id');
    }

    public function rivalVideos()
    {
        return $this->hasMany(Video::class, 'rival_team_id');
    }

    public function scopeOwnTeam($query)
    {
        return $query->where('is_own_team', true);
    }

    public function scopeRivalTeams($query)
    {
        return $query->where('is_own_team', false);
    }
}
