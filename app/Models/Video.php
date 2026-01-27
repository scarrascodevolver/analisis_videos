<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Video extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'thumbnail_path',
        'file_name',
        'file_size',
        'mime_type',
        'duration',
        'timeline_offset', // Offset en segundos para sincronizar clips con el video
        'uploaded_by',
        'analyzed_team_name', // Nombre del equipo analizado (= organización)
        'rival_team_name',    // Nombre del rival (texto libre)
        'category_id',
        'division',
        'rugby_situation_id',
        'match_date',
        'status',
        'visibility_type',
        // Compression fields
        'processing_status',
        'original_file_size',
        'compressed_file_size',
        'original_file_path',
        'compression_ratio',
        'processing_started_at',
        'processing_completed_at',
        // Multi-camera fields
        'video_group_id',
        'is_master',
        'camera_angle',
        'sync_offset',
        'is_synced',
        'sync_reference_event',
    ];

    protected function casts(): array
    {
        return [
            'timeline_offset' => 'decimal:2',
            'match_date' => 'date',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
        ];
    }

    /**
     * Boot method to cancel compression jobs when video is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($video) {
            // Cancel any pending compression jobs for this video
            // Jobs are stored with video ID in the payload as JSON
            $deletedCount = DB::table('jobs')
                ->where('payload', 'like', '%CompressVideoJob%')
                ->where('payload', 'like', "%\"videoId\":{$video->id}%")
                ->delete();

            if ($deletedCount > 0) {
                Log::info("Video {$video->id} deleting: Cancelled {$deletedCount} pending compression job(s)");
            }
        });
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obtener nombre del rival
     */
    public function getRivalNameAttribute(): ?string
    {
        return $this->rival_team_name;
    }

    /**
     * Verificar si tiene rival
     */
    public function hasRival(): bool
    {
        return !empty($this->rival_team_name);
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

    public function annotations()
    {
        return $this->hasMany(VideoAnnotation::class);
    }

    public function clips()
    {
        return $this->hasMany(VideoClip::class);
    }

    public function views()
    {
        return $this->hasMany(VideoView::class);
    }

    public function rugbySituation()
    {
        return $this->belongsTo(RugbySituation::class);
    }

    /**
     * Get total view count for this video
     */
    public function getViewCountAttribute()
    {
        return $this->views()->count();
    }

    /**
     * Get unique viewers count for this video
     */
    public function getUniqueViewersAttribute()
    {
        return $this->views()->distinct('user_id')->count('user_id');
    }

    /**
     * Get view statistics for this video
     */
    public function getViewStats()
    {
        $stats = $this->views()
            ->selectRaw('user_id, COUNT(*) as view_count, MAX(viewed_at) as last_viewed')
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderByDesc('last_viewed') // Ordenar por las más recientes primero
            ->get();

        // Formatear last_viewed como timestamp Unix (segundos) para evitar problemas de timezone
        return $stats->map(function($stat) {
            $stat->last_viewed_timestamp = strtotime($stat->last_viewed);
            return $stat;
        });
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

    /**
     * Buscar videos por nombre de equipo (analizado o rival)
     */
    public function scopeByTeamName($query, $teamName)
    {
        return $query->where(function($q) use ($teamName) {
            $q->where('analyzed_team_name', 'LIKE', "%{$teamName}%")
              ->orWhere('rival_team_name', 'LIKE', "%{$teamName}%");
        });
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

    /**
     * ==========================================
     * Multi-Camera / Multi-Angle Methods
     * ==========================================
     */

    /**
     * Check if this video is part of a multi-camera group
     */
    public function isPartOfGroup(): bool
    {
        return !is_null($this->video_group_id);
    }

    /**
     * Check if this video is the master of its group
     */
    public function isMaster(): bool
    {
        return $this->is_master === true;
    }

    /**
     * Check if this video is a slave (secondary angle)
     */
    public function isSlave(): bool
    {
        return $this->isPartOfGroup() && !$this->isMaster();
    }

    /**
     * Check if this video has been synced with the master
     */
    public function isSynced(): bool
    {
        return $this->is_synced === true && !is_null($this->sync_offset);
    }

    /**
     * Get all videos in the same group (including this one)
     */
    public function groupVideos()
    {
        if (!$this->isPartOfGroup()) {
            return collect([$this]);
        }

        return Video::where('video_group_id', $this->video_group_id)
            ->orderByDesc('is_master')
            ->orderBy('camera_angle')
            ->get();
    }

    /**
     * Get the master video of this group
     */
    public function getMasterVideo()
    {
        if ($this->isMaster()) {
            return $this;
        }

        if (!$this->isPartOfGroup()) {
            return null;
        }

        return Video::where('video_group_id', $this->video_group_id)
            ->where('is_master', true)
            ->first();
    }

    /**
     * Get all slave videos (secondary angles) of this group
     */
    public function getSlaveVideos()
    {
        if (!$this->isPartOfGroup()) {
            return collect();
        }

        return Video::where('video_group_id', $this->video_group_id)
            ->where('is_master', false)
            ->orderBy('camera_angle')
            ->get();
    }

    /**
     * Get only synced slave videos
     */
    public function getSyncedSlaveVideos()
    {
        return $this->getSlaveVideos()->filter(function ($video) {
            return $video->isSynced();
        });
    }

    /**
     * Get only unsynced slave videos
     */
    public function getUnsyncedSlaveVideos()
    {
        return $this->getSlaveVideos()->filter(function ($video) {
            return !$video->isSynced();
        });
    }

    /**
     * Generate a unique group ID for multi-camera videos
     */
    public static function generateGroupId(): string
    {
        return 'group_' . time() . '_' . uniqid();
    }

    /**
     * Associate this video as a slave to a master video
     */
    public function associateToMaster(Video $masterVideo, string $cameraAngle): bool
    {
        \Log::info("associateToMaster called - Master ID: {$masterVideo->id}, Master is_master: {$masterVideo->is_master}, Master group_id: {$masterVideo->video_group_id}");

        if (!$masterVideo->isMaster()) {
            \Log::warning("associateToMaster failed - master video is not actually a master");
            return false;
        }

        $this->update([
            'video_group_id' => $masterVideo->video_group_id,
            'is_master' => false,
            'camera_angle' => $cameraAngle,
            'is_synced' => false,
            'sync_offset' => null,
        ]);

        \Log::info("associateToMaster succeeded - Slave ID: {$this->id} assigned to group {$this->video_group_id} as {$cameraAngle}");
        return true;
    }

    /**
     * Sync this slave video with the master
     */
    public function syncWithMaster(float $offset, ?string $referenceEvent = null): bool
    {
        if (!$this->isSlave()) {
            return false;
        }

        $this->update([
            'sync_offset' => $offset,
            'is_synced' => true,
            'sync_reference_event' => $referenceEvent,
        ]);

        return true;
    }

    /**
     * Remove this video from its group
     */
    public function removeFromGroup(): bool
    {
        $this->update([
            'video_group_id' => null,
            'is_master' => true,
            'camera_angle' => null,
            'sync_offset' => null,
            'is_synced' => false,
            'sync_reference_event' => null,
        ]);

        return true;
    }

    public static function getPlayerCategory($position)
    {
        if (is_null($position)) {
            return 'backs';
        }

        // Posiciones Forward - incluye valores del formulario de registro
        $forwardPositions = [
            // Inglés (legacy)
            'Prop Izquierdo (#1)', 'Hooker (#2)', 'Prop Derecho (#3)',
            'Segunda Línea (#4)', 'Segunda Línea (#5)',
            'Ala Ciega (#6)', 'Ala Abierta (#7)', 'Octavo (#8)',
            'Primera Línea', 'Hooker/Prop Suplente', 'Segunda Línea Suplente',
            'Tercera Línea Suplente', 'Entrenador de Forwards',

            // Español (formulario de registro)
            'pilar_izquierdo', 'hooker', 'pilar_derecho',
            'segunda_linea_4', 'segunda_linea_5',
            'ala_ciega', 'ala_abierta', 'octavo',
            'ala_izquierdo_forward', 'ala_derecho_forward'
        ];

        // Verificar si la posición es Forward
        foreach ($forwardPositions as $forwardPos) {
            if (stripos($position, $forwardPos) !== false ||
                stripos($position, 'primera línea') !== false ||
                stripos($position, 'segunda línea') !== false ||
                stripos($position, 'tercera línea') !== false ||
                preg_match('/#[1-8]\)/', $position)) {
                return 'forwards';
            }
        }

        // Si no es Forward, es Back
        return 'backs';
    }
}
