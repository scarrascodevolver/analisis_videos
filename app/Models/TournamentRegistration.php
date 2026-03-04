<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegistration extends Model
{
    protected $fillable = [
        'tournament_id',
        'club_organization_id',
        'status',
        'registered_at',
        'withdrawn_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'withdrawn_at'  => 'datetime',
            'rejected_at'   => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function clubOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'club_organization_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
