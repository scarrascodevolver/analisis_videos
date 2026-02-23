<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lineup extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'video_id',
        'organization_id',
        'created_by',
        'team_type',
        'formation',
        'notes',
    ];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(LineupPlayer::class)
            ->orderBy('position_number')
            ->orderBy('shirt_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function starters(): HasMany
    {
        return $this->hasMany(LineupPlayer::class)
            ->where('status', 'starter')
            ->orderBy('position_number');
    }

    public function substitutes(): HasMany
    {
        return $this->hasMany(LineupPlayer::class)
            ->where('status', 'substitute')
            ->orderBy('shirt_number');
    }
}
