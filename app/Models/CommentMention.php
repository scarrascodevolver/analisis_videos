<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentMention extends Model
{
    protected $fillable = [
        'comment_id',
        'mentioned_user_id',
        'mentioned_by_user_id',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * El comentario donde ocurrió la mención
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(VideoComment::class, 'comment_id');
    }

    /**
     * El usuario que fue mencionado
     */
    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_user_id');
    }

    /**
     * El usuario que hizo la mención
     */
    public function mentionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentioned_by_user_id');
    }

    /**
     * Scope para menciones no leídas
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope para menciones de un usuario específico
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('mentioned_user_id', $userId);
    }

    /**
     * Marcar mención como leída
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }
}
