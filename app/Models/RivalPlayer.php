<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RivalPlayer extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'rival_team_id',
        'organization_id',
        'name',
        'shirt_number',
        'usual_position',
        'notes',
    ];

    public function rivalTeam(): BelongsTo
    {
        return $this->belongsTo(RivalTeam::class);
    }

    public function lineupPlayers(): HasMany
    {
        return $this->hasMany(LineupPlayer::class);
    }

    public function getPositionLabelAttribute(): string
    {
        $positions = [
            1 => 'Pilar izq.',
            2 => 'Hooker',
            3 => 'Pilar der.',
            4 => 'Lock izq.',
            5 => 'Lock der.',
            6 => 'Ala ciego',
            7 => 'Ala abierto',
            8 => 'Octavo',
            9 => 'Medio scrum',
            10 => 'Apertura',
            11 => 'Ala izq.',
            12 => 'Centro izq.',
            13 => 'Centro der.',
            14 => 'Ala der.',
            15 => 'Fullback',
        ];

        return $positions[$this->usual_position] ?? '—';
    }
}
