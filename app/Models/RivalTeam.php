<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class RivalTeam extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'city',
        'notes',
    ];

    /**
     * Get all videos that have this rival team
     */
    public function videos()
    {
        return $this->hasMany(Video::class, 'rival_team_id');
    }

    /**
     * Scope for searching rival teams
     */
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
              ->orWhere('code', 'LIKE', "%{$term}%")
              ->orWhere('city', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Get formatted display name (includes city if available)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->city) {
            return "{$this->name} ({$this->city})";
        }

        return $this->name;
    }
}
