<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoComment extends Model
{
    protected $fillable = [
        'video_id',
        'user_id',
        'parent_id',
        'comment',
        'timestamp_seconds',
        'category',
        'priority',
        'status',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(VideoComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(VideoComment::class, 'parent_id');
    }

    /**
     * Menciones en este comentario
     */
    public function mentions()
    {
        return $this->hasMany(CommentMention::class, 'comment_id');
    }

    /**
     * Usuarios mencionados en este comentario
     */
    public function mentionedUsers()
    {
        return $this->belongsToMany(User::class, 'comment_mentions', 'comment_id', 'mentioned_user_id')
            ->withPivot('is_read')
            ->withTimestamps();
    }

    /**
     * Solo jugadores mencionados
     */
    public function mentionedPlayers()
    {
        return $this->mentionedUsers()->where('role', 'jugador');
    }

    /**
     * Staff mencionado (entrenadores, analistas)
     */
    public function mentionedStaff()
    {
        return $this->mentionedUsers()->whereIn('role', ['entrenador', 'analista', 'staff', 'director_tecnico']);
    }

    /**
     * Assignments creados desde este comentario
     */
    public function assignments()
    {
        return $this->hasMany(VideoAssignment::class, 'comment_id');
    }

    public function getFormattedTimestampAttribute()
    {
        $minutes = floor($this->timestamp_seconds / 60);
        $seconds = $this->timestamp_seconds % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }
}
