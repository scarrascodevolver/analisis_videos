<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSwitchAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'from_organization_id',
        'to_organization_id',
        'ip_address',
        'user_agent',
        'source_url',
        'switch_reason',
        'switched_at',
    ];

    protected function casts(): array
    {
        return [
            'switched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    public function toOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'to_organization_id');
    }
}

