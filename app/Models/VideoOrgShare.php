<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoOrgShare extends Model
{
    protected $fillable = [
        'video_id',
        'source_organization_id',
        'target_organization_id',
        'target_category_id',
        'division_id',
        'shared_by',
        'status',
        'notes',
        'shared_at',
        'revoked_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'shared_at'  => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function sourceOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'source_organization_id');
    }

    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_organization_id');
    }

    public function targetCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'target_category_id');
    }

    public function sharedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(TournamentDivision::class, 'division_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
