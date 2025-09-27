<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoAnnotation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'video_id',
        'user_id',
        'timestamp',
        'annotation_data',
        'annotation_type',
        'is_visible',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'annotation_data' => 'array',
        'timestamp' => 'decimal:2',
        'is_visible' => 'boolean',
    ];

    /**
     * Get the video that owns the annotation.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get the user that created the annotation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include visible annotations.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope a query to filter annotations by timestamp range.
     */
    public function scopeAtTimestamp($query, $timestamp, $tolerance = 0.5)
    {
        return $query->whereBetween('timestamp', [
            $timestamp - $tolerance,
            $timestamp + $tolerance
        ]);
    }

    /**
     * Scope a query to order annotations by timestamp.
     */
    public function scopeOrderedByTimestamp($query)
    {
        return $query->orderBy('timestamp', 'asc');
    }
}