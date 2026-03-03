<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use BelongsToOrganization;

    protected $fillable = ['organization_id', 'name', 'season', 'is_public'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(TournamentDivision::class)->orderBy('order')->orderBy('name');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
