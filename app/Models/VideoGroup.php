<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VideoGroup extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'organization_id',
    ];

    /**
     * Get all videos in this group
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class, 'video_group_video')
            ->withPivot([
                'is_master',
                'camera_angle',
                'sync_offset',
                'is_synced',
                'sync_reference_event',
            ])
            ->withTimestamps()
            ->orderByPivot('is_master', 'desc')
            ->orderByPivot('camera_angle');
    }

    /**
     * Get the master video of this group
     */
    public function getMasterVideo(): ?Video
    {
        return $this->videos()
            ->wherePivot('is_master', true)
            ->first();
    }

    /**
     * Get all slave videos (secondary angles) of this group
     */
    public function getSlaveVideos()
    {
        return $this->videos()
            ->wherePivot('is_master', false)
            ->get();
    }

    /**
     * Get only synced slave videos
     */
    public function getSyncedSlaveVideos()
    {
        return $this->videos()
            ->wherePivot('is_master', false)
            ->wherePivot('is_synced', true)
            ->get();
    }

    /**
     * Get only unsynced slave videos
     */
    public function getUnsyncedSlaveVideos()
    {
        return $this->videos()
            ->wherePivot('is_master', false)
            ->wherePivot('is_synced', false)
            ->get();
    }

    /**
     * Check if this group has a master video
     */
    public function hasMaster(): bool
    {
        return $this->videos()->wherePivot('is_master', true)->exists();
    }

    /**
     * Get video count in this group
     */
    public function getVideoCountAttribute(): int
    {
        return $this->videos()->count();
    }
}
