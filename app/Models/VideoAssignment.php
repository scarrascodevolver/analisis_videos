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

    public function scopeForPlayer($query, $playerId)
    {
        return $query->where('assigned_to', $playerId);
    }
}
