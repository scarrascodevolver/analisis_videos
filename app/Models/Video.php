<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'thumbnail_path',
        'file_name',
        'file_size',
        'mime_type',
        'duration',
        'uploaded_by',
        'analyzed_team_id',
        'rival_team_id',
        'category_id',
        'rugby_situation_id',
        'match_date',
        'status',
        'visibility_type',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'date',
        ];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function analyzedTeam()
    {
        return $this->belongsTo(Team::class, 'analyzed_team_id');
    }

    public function rivalTeam()
    {
        return $this->belongsTo(Team::class, 'rival_team_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(VideoComment::class);
    }

    public function assignments()
    {
        return $this->hasMany(VideoAssignment::class);
    }

    public function rugbySituation()
    {
        return $this->belongsTo(RugbySituation::class);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByRugbySituation($query, $situationId)
    {
        return $query->where('rugby_situation_id', $situationId);
    }

    public function scopeByRugbyCategory($query, $rugbyCategory)
    {
        return $query->whereHas('rugbySituation', function($q) use ($rugbyCategory) {
            $q->where('category', $rugbyCategory);
        });
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('analyzed_team_id', $teamId);
    }

    public function scopeVisibleForUser($query, $user)
    {
        if (in_array($user->role, ['analista', 'entrenador', 'staff'])) {
            return $query;
        }

        if ($user->role === 'jugador') {
            $userPosition = $user->profile?->position;
            $playerCategory = $this->getPlayerCategory($userPosition);

            return $query->where(function($q) use ($user, $playerCategory) {
                $q->where('visibility_type', 'public')
                  ->orWhere('visibility_type', $playerCategory)
                  ->orWhereHas('assignments', function($assignQ) use ($user) {
                      $assignQ->where('assigned_to', $user->id);
                  });
            });
        }

        return $query;
    }

    public static function getPlayerCategory($position)
    {
        if (is_null($position)) {
            return 'backs';
        }

        // Convert position text to category
        $forwardPositions = [
            'Prop Izquierdo (#1)', 'Hooker (#2)', 'Prop Derecho (#3)',
            'Segunda Línea (#4)', 'Segunda Línea (#5)',
            'Ala Ciega (#6)', 'Ala Abierta (#7)', 'Octavo (#8)',
            'Primera Línea', 'Hooker/Prop Suplente', 'Segunda Línea Suplente',
            'Tercera Línea Suplente', 'Entrenador de Forwards'
        ];

        // Check if position contains any forward indicators
        foreach ($forwardPositions as $forwardPos) {
            if (stripos($position, $forwardPos) !== false ||
                stripos($position, 'primera línea') !== false ||
                stripos($position, 'segunda línea') !== false ||
                stripos($position, 'tercera línea') !== false ||
                preg_match('/#[1-8]\)/', $position)) {
                return 'forwards';
            }
        }

        return 'backs';
    }
}
