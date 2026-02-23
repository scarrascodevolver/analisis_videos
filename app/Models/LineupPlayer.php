<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineupPlayer extends Model
{
    protected $fillable = [
        'lineup_id',
        'user_id',
        'rival_player_id',
        'player_name',
        'shirt_number',
        'position_number',
        'status',
        'substitution_minute',
    ];

    public function lineup(): BelongsTo
    {
        return $this->belongsTo(Lineup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rivalPlayer(): BelongsTo
    {
        return $this->belongsTo(RivalPlayer::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        if ($this->rivalPlayer) {
            return $this->rivalPlayer->name;
        }

        return $this->player_name ?? 'Sin nombre';
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

        return $positions[$this->position_number] ?? 'Banco';
    }
}
