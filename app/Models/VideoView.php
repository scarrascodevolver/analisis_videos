<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoView extends Model
{
    protected $fillable = [
        'video_id',
        'user_id',
        'viewed_at',
        'watch_duration',
        'completed',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'completed' => 'boolean',
        ];
    }

    /**
     * Get the video that was viewed
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the user who viewed the video
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this view was within the cooldown period
     */
    public static function isWithinCooldown($videoId, $userId, $minutes = 30): bool
    {
        $lastView = self::where('video_id', $videoId)
            ->where('user_id', $userId)
            ->latest('viewed_at')
            ->first();

        if (! $lastView) {
            return false;
        }

        return $lastView->viewed_at->diffInMinutes(now()) < $minutes;
    }
}
