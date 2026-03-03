<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentDivision extends Model
{
    protected $fillable = ['tournament_id', 'name', 'order'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentRegistration::class, 'division_id');
    }

    public function videoShares(): HasMany
    {
        return $this->hasMany(VideoOrgShare::class, 'division_id');
    }
}
