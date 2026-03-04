<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class VideoClip extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'video_id',
        'clip_category_id',
        'organization_id',
        'created_by',
        'start_time',
        'end_time',
        'sort_order',
        'title',
        'notes',
        'players',
        'tags',
        'rating',
        'is_highlight',
        'is_shared',
        'share_token',
    ];

    protected function casts(): array
    {
        return [
            'start_time'  => 'decimal:2',
            'end_time'    => 'decimal:2',
            'players'     => 'array',
            'tags'        => 'array',
            'rating'      => 'integer',
            'is_highlight' => 'boolean',
            'is_shared'   => 'boolean',
        ];
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function category()
    {
        return $this->belongsTo(ClipCategory::class, 'clip_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Duración en segundos
    public function getDurationAttribute()
    {
        return $this->end_time - $this->start_time;
    }

    // Formato MM:SS para mostrar
    public function getFormattedStartAttribute()
    {
        $seconds = (int) $this->start_time;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $secs);
    }

    public function getFormattedEndAttribute()
    {
        $seconds = (int) $this->end_time;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $secs);
    }

    public function getFormattedDurationAttribute()
    {
        $seconds = (int) $this->duration;

        return sprintf('%02d:%02d', floor($seconds / 60), $seconds % 60);
    }

    // Scopes

    /**
     * Todos los miembros de la organización ven todos los clips.
     * El filtrado por is_shared fue eliminado: la visibilidad es intra-org
     * y no tiene sentido restringirla dentro del mismo tenant.
     */
    public function scopeVisibleTo($query, $user)
    {
        // Todos los miembros de la org ven todos los clips
        return $query;
    }

    public function scopeForVideo($query, $videoId)
    {
        return $query->where('video_id', $videoId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('clip_category_id', $categoryId);
    }

    public function scopeHighlights($query)
    {
        return $query->where('is_highlight', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('start_time');
    }
}
