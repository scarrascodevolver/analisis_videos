<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoAssignment extends Model
{
    protected $fillable = [
        'video_id',
        'assigned_by',
        'assigned_to',
        'notes',
        'comment_id',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Comentario que generó esta asignación (si aplica)
     */
    public function comment()
    {
        return $this->belongsTo(VideoComment::class, 'comment_id');
    }

    public function scopeForPlayer($query, $playerId)
    {
        return $query->where('assigned_to', $playerId);
    }

    /**
     * Asignaciones creadas desde menciones en comentarios
     */
    public function scopeFromMention($query)
    {
        return $query->whereNotNull('comment_id');
    }
}
