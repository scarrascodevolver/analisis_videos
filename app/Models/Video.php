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
        'division',
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

    public function scopeByDivision($query, $division)
    {
        return $query->where('division', $division);
    }

    public function scopeVisibleForUser($query, $user)
    {
        if (in_array($user->role, ['analista', 'entrenador', 'staff', 'director_tecnico'])) {
            return $query; // Staff ve todos los videos
        }

        if ($user->role === 'jugador') {
            $userCategoryId = $user->profile?->user_category_id;
            $userPosition = $user->profile?->position;
            $playerCategory = $this->getPlayerCategory($userPosition);

            return $query->where(function($q) use ($user, $userCategoryId, $playerCategory) {
                // Solo videos de la misma categoría del usuario (o si no tiene categoría, ve todos los públicos)
                if ($userCategoryId) {
                    $q->where('category_id', $userCategoryId);
                }

                // Además debe cumplir con el tipo de visibilidad
                $q->where(function($visQ) use ($user, $playerCategory) {
                    $visQ->where('visibility_type', 'public')
                         ->orWhere('visibility_type', $playerCategory)
                         ->orWhereHas('assignments', function($assignQ) use ($user) {
                             $assignQ->where('assigned_to', $user->id);
                         });
                });
            });
        }

        return $query;
    }

    /**
     * Scope para "Videos del Equipo" - NO incluye videos específicos
     * Solo videos públicos y por categoría de posición (forwards/backs)
     */
    public function scopeTeamVisible($query, $user)
    {
        // Analistas, staff y directores ven todos los videos
        if (in_array($user->role, ['analista', 'staff', 'director_tecnico', 'director_club'])) {
            return $query;
        }

        // Entrenadores solo ven videos de su categoría asignada
        if ($user->role === 'entrenador') {
            $coachCategoryId = $user->profile?->user_category_id;

            if ($coachCategoryId) {
                return $query->where('category_id', $coachCategoryId);
            } else {
                // Si el entrenador no tiene categoría asignada, no ve ningún video
                return $query->whereRaw('1 = 0');
            }
        }

        // Jugadores ven videos de su categoría + filtros de visibilidad
        if ($user->role === 'jugador') {
            $userCategoryId = $user->profile?->user_category_id;
            $userPosition = $user->profile?->position;
            $playerCategory = $this->getPlayerCategory($userPosition);

            return $query->where(function($q) use ($user, $userCategoryId, $playerCategory) {
                // Solo videos de la misma categoría del usuario
                if ($userCategoryId) {
                    $q->where('category_id', $userCategoryId);
                }

                // SOLO tipos de visibilidad pública y por posición - NO incluir 'specific'
                $q->where(function($visQ) use ($playerCategory) {
                    $visQ->where('visibility_type', 'public')
                         ->orWhere('visibility_type', $playerCategory);
                });
            });
        }

        return $query;
    }

    /**
     * Scope para "Mis Videos" - Solo videos específicamente asignados al usuario
     */
    public function scopeMyAssignedVideos($query, $user)
    {
        return $query->whereHas('assignments', function($assignQ) use ($user) {
            $assignQ->where('assigned_to', $user->id);
        });
    }

    /**
     * Scope para Entrenadores - Solo videos de su categoría asignada
     * Analistas y staff ven todos los videos
     */
    public function scopeCoachVisible($query, $user)
    {
        // Analistas, staff y directores ven todos los videos
        if (in_array($user->role, ['analista', 'staff', 'director_tecnico', 'director_club'])) {
            return $query;
        }

        // Entrenadores solo ven videos de su categoría
        if ($user->role === 'entrenador') {
            $coachCategoryId = $user->profile?->user_category_id;

            if ($coachCategoryId) {
                return $query->where('category_id', $coachCategoryId);
            } else {
                // Si el entrenador no tiene categoría asignada, no ve ningún video
                return $query->whereRaw('1 = 0'); // Devuelve query vacío
            }
        }

        // Para cualquier otro rol, aplicar el filtro normal
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
