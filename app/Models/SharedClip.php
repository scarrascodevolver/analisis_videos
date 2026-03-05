<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedClip extends Model
{
    // Cross-org model — no BelongsToOrganization global scope
    protected $fillable = [
        'video_clip_id',
        'video_id',
        'shared_by_user_id',
        'shared_with_user_id',
        'from_organization_id',
        'to_organization_id',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function clip()
    {
        return $this->belongsTo(VideoClip::class, 'video_clip_id')->withoutGlobalScopes();
    }

    public function video()
    {
        return $this->belongsTo(Video::class)->withoutGlobalScopes();
    }

    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function sharedWith()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function fromOrganization()
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    public function toOrganization()
    {
        return $this->belongsTo(Organization::class, 'to_organization_id');
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}
