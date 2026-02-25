<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        'rival_team_id',      // FK to rival_teams table
        'rival_team_name',    // Nombre del rival (texto libre - fallback)
        'category_id',
        'tournament_id',
        'club_id',
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
        // Bunny Stream
        'bunny_video_id',
        'bunny_hls_url',
        'bunny_thumbnail',
        'bunny_status',
        'bunny_mp4_url',
        // YouTube
        'is_youtube_video',
        'youtube_url',
        'youtube_video_id',
    ];

    protected function casts(): array
    {
        return [
            'timeline_offset' => 'decimal:2',
            'match_date' => 'date',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
            'is_youtube_video' => 'boolean',
        ];
    }

    /**
     * Extrae el video ID de una URL de YouTube.
     * Soporta: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
     */
    public static function extractYoutubeVideoId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*&v=([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Boot method to cancel compression jobs when video is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($video) {
            // Best effort cleanup: nunca bloquear el borrado por fallas en subsistemas auxiliares.
            try {
                static $jobsTableExists = null;
                if ($jobsTableExists === null) {
                    $jobsTableExists = Schema::hasTable('jobs');
                }

                if ($jobsTableExists) {
                    $deletedCount = DB::table('jobs')
                        ->where('payload', 'like', '%CompressVideoJob%')
                        ->where('payload', 'like', "%\"videoId\":{$video->id}%")
                        ->delete();

                    if ($deletedCount > 0) {
                        Log::info("Video {$video->id} deleting: Cancelled {$deletedCount} pending compression job(s)");
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Video {$video->id} deleting: could not cleanup jobs table", [
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $assignmentsDeleted = $video->assignments()->delete();
                if ($assignmentsDeleted > 0) {
                    Log::info("Video {$video->id} deleting: Removed {$assignmentsDeleted} assignment(s)");
                }
            } catch (\Throwable $e) {
                Log::warning("Video {$video->id} deleting: could not cleanup assignments", [
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Relationship to RivalTeam
     */
    public function rivalTeam()
    {
        return $this->belongsTo(RivalTeam::class);
    }

    /**
     * Obtener nombre del rival (usa RivalTeam si existe, sino fallback a rival_team_name)
     */
    public function getRivalNameAttribute(): ?string
    {
        // Priority: rival_team relationship > rival_team_name fallback
        return $this->rivalTeam?->name ?? $this->rival_team_name;
    }

    /**
     * Verificar si tiene rival
     */
    public function hasRival(): bool
    {
        return $this->rival_team_id !== null || ! empty($this->rival_team_name);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function club()
    {
        return $this->belongsTo(\App\Models\Club::class);
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

    public function lineups()
    {
        return $this->hasMany(Lineup::class);
    }

    public function localLineup()
    {
        return $this->hasOne(Lineup::class)->where('team_type', 'local');
    }

    public function rivalLineup()
    {
        return $this->hasOne(Lineup::class)->where('team_type', 'rival');
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
     * New many-to-many relationship with VideoGroups
     * A video can belong to multiple groups simultaneously
     */
    public function videoGroups()
    {
        return $this->belongsToMany(VideoGroup::class, 'video_group_video')
            ->withPivot([
                'is_master',
                'camera_angle',
                'sync_offset',
                'is_synced',
                'sync_reference_event',
            ])
            ->withTimestamps();
    }

    /**
     * Get total view count for this video (all starts)
     */
    public function getViewCountAttribute()
    {
        return $this->views()->count();
    }

    /**
     * Get valid view count (meets viewing criteria)
     */
    public function getValidViewCountAttribute()
    {
        return $this->views()->where('is_valid_view', true)->count();
    }

    /**
     * Get completion count (videos watched to the end)
     */
    public function getCompletionCountAttribute()
    {
        return $this->views()->where('completed', true)->count();
    }

    /**
     * Get unique viewers count for this video
     */
    public function getUniqueViewersAttribute()
    {
        return $this->views()->distinct('user_id')->count('user_id');
    }

    /**
     * Get unique valid viewers count
     */
    public function getUniqueValidViewersAttribute()
    {
        return $this->views()
            ->where('is_valid_view', true)
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get view statistics for this video
     */
    public function getViewStats()
    {
        $videoDuration = $this->duration ?? 1; // Avoid division by zero

        $stats = $this->views()
            ->selectRaw('
                user_id,
                COUNT(*) as view_count,
                SUM(CASE WHEN is_valid_view = 1 THEN 1 ELSE 0 END) as valid_view_count,
                MAX(viewed_at) as last_viewed,
                SUM(watch_duration) as total_watch_time,
                MAX(completed) as is_completed,
                MAX(is_valid_view) as has_valid_view
            ')
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderByDesc('last_viewed')
            ->get();

        // Format data and calculate percentages
        return $stats->map(function ($stat) use ($videoDuration) {
            $stat->last_viewed_timestamp = strtotime($stat->last_viewed);
            $stat->total_watch_time = $stat->total_watch_time ?? 0;
            $stat->is_completed = (bool) $stat->is_completed;
            $stat->has_valid_view = (bool) $stat->has_valid_view;
            $stat->valid_view_count = (int) $stat->valid_view_count;

            // Calculate watched percentage
            $stat->watched_percentage = $videoDuration > 0
                ? min(100, ($stat->total_watch_time / $videoDuration) * 100)
                : 0;

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
        return $query->whereHas('rugbySituation', function ($q) use ($rugbyCategory) {
            $q->where('category', $rugbyCategory);
        });
    }

    /**
     * Buscar videos por nombre de equipo (analizado o rival)
     */
    public function scopeByTeamName($query, $teamName)
    {
        return $query->where(function ($q) use ($teamName) {
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
        if (in_array($user->role, ['analista', 'entrenador', 'staff', 'director_tecnico', 'super_admin'])) {
            return $query; // Staff ve todos los videos
        }

        if ($user->role === 'jugador') {
            $userCategoryId = $user->profile?->user_category_id;
            $userPosition = $user->profile?->position;
            $playerCategory = $this->getPlayerCategory($userPosition);

            return $query->where(function ($q) use ($user, $userCategoryId, $playerCategory) {
                // Solo videos de la misma categoría del usuario (o si no tiene categoría, ve todos los públicos)
                if ($userCategoryId) {
                    $q->where('category_id', $userCategoryId);
                }

                // Además debe cumplir con el tipo de visibilidad
                $q->where(function ($visQ) use ($user, $playerCategory) {
                    $visQ->where('visibility_type', 'public')
                        ->orWhere('visibility_type', $playerCategory)
                        ->orWhereHas('assignments', function ($assignQ) use ($user) {
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

            return $query->where(function ($q) use ($userCategoryId, $playerCategory) {
                // Solo videos de la misma categoría del usuario
                if ($userCategoryId) {
                    $q->where('category_id', $userCategoryId);
                }

                // SOLO tipos de visibilidad pública y por posición - NO incluir 'specific'
                $q->where(function ($visQ) use ($playerCategory) {
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
        return $query->whereHas('assignments', function ($assignQ) use ($user) {
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
     * REFACTORED: Now supports multiple groups per video
     * ==========================================
     */

    /**
     * Check if this video is part of ANY multi-camera group
     */
    public function isPartOfGroup(): bool
    {
        return $this->videoGroups()->exists();
    }

    /**
     * Check if this video is the master in ANY group or a specific group
     *
     * @param  int|null  $groupId  Specific group to check, null checks all groups
     */
    public function isMaster(?int $groupId = null): bool
    {
        if ($groupId) {
            return $this->videoGroups()
                ->where('video_groups.id', $groupId)
                ->wherePivot('is_master', true)
                ->exists();
        }

        // Check if master in any group
        return $this->videoGroups()->wherePivot('is_master', true)->exists();
    }

    /**
     * Check if this video is a slave in ANY group or a specific group
     *
     * @param  int|null  $groupId  Specific group to check, null checks all groups
     */
    public function isSlave(?int $groupId = null): bool
    {
        if ($groupId) {
            return $this->videoGroups()
                ->where('video_groups.id', $groupId)
                ->wherePivot('is_master', false)
                ->exists();
        }

        // Check if slave in any group
        return $this->videoGroups()->wherePivot('is_master', false)->exists();
    }

    /**
     * Get all groups this video belongs to
     */
    public function getGroups()
    {
        return $this->videoGroups()->get();
    }

    /**
     * Check if video is in a specific group
     */
    public function isInGroup(int $groupId): bool
    {
        return $this->videoGroups()->where('video_groups.id', $groupId)->exists();
    }

    /**
     * Check if video is master in a specific group
     */
    public function isMasterInGroup(int $groupId): bool
    {
        return $this->videoGroups()
            ->where('video_groups.id', $groupId)
            ->wherePivot('is_master', true)
            ->exists();
    }

    /**
     * Check if this video has been synced with the master in a specific group
     *
     * @param  int|null  $groupId  Specific group to check, null checks any group
     */
    public function isSynced(?int $groupId = null): bool
    {
        if ($groupId) {
            $pivot = $this->videoGroups()
                ->where('video_groups.id', $groupId)
                ->first();

            if ($pivot) {
                return $pivot->pivot->is_synced === true && ! is_null($pivot->pivot->sync_offset);
            }

            return false;
        }

        // Check if synced in any group
        return $this->videoGroups()
            ->wherePivot('is_synced', true)
            ->whereNotNull('sync_offset')
            ->exists();
    }

    /**
     * Get all videos in the same group (including this one)
     *
     * @param  int|null  $groupId  Specific group ID, if null uses first group or old system
     */
    public function groupVideos(?int $groupId = null)
    {
        if ($groupId) {
            $group = VideoGroup::find($groupId);
            if ($group) {
                return $group->videos;
            }

            return collect();
        }

        // Get first group
        $firstGroup = $this->videoGroups()->first();
        if ($firstGroup) {
            return $firstGroup->videos;
        }

        // Not in any group, return just this video
        return collect([$this]);
    }

    /**
     * Get the master video of a specific group
     *
     * @param  int|null  $groupId  Specific group ID, if null uses first group or old system
     */
    public function getMasterVideo(?int $groupId = null)
    {
        if ($groupId) {
            $group = VideoGroup::find($groupId);
            if ($group) {
                return $group->getMasterVideo();
            }

            return null;
        }

        // Get master from first group
        $firstGroup = $this->videoGroups()->first();
        if ($firstGroup) {
            return $firstGroup->getMasterVideo();
        }

        // If this video is master of a group, return itself
        if ($this->isMaster()) {
            return $this;
        }

        return null;
    }

    /**
     * Get all slave videos (secondary angles) of a specific group
     *
     * @param  int|null  $groupId  Specific group ID, if null uses first group or old system
     */
    public function getSlaveVideos(?int $groupId = null)
    {
        if ($groupId) {
            $group = VideoGroup::find($groupId);
            if ($group) {
                return $group->getSlaveVideos();
            }

            return collect();
        }

        // Get slaves from first group
        $firstGroup = $this->videoGroups()->first();
        if ($firstGroup) {
            return $firstGroup->getSlaveVideos();
        }

        // Not in any group
        return collect();
    }

    /**
     * Get only synced slave videos of a specific group
     */
    public function getSyncedSlaveVideos(?int $groupId = null)
    {
        return $this->getSlaveVideos($groupId)->filter(function ($video) use ($groupId) {
            return $video->isSynced($groupId);
        });
    }

    /**
     * Get only unsynced slave videos of a specific group
     */
    public function getUnsyncedSlaveVideos(?int $groupId = null)
    {
        return $this->getSlaveVideos($groupId)->filter(function ($video) use ($groupId) {
            return ! $video->isSynced($groupId);
        });
    }

    /**
     * Generate a unique group ID for multi-camera videos (DEPRECATED - kept for old system)
     */
    public static function generateGroupId(): string
    {
        return 'group_'.time().'_'.uniqid();
    }

    /**
     * Associate this video as a slave to a master video in a specific group
     *
     * @param  Video  $masterVideo  The master video
     * @param  string  $cameraAngle  Camera angle name
     * @param  int|null  $groupId  Specific group ID, if null creates/uses first group
     */
    public function associateToMaster(Video $masterVideo, string $cameraAngle, ?int $groupId = null): bool
    {
        \Log::info("associateToMaster() - Master ID: {$masterVideo->id}, Slave ID: {$this->id}, Group ID: {$groupId}, Angle: {$cameraAngle}");

        // NEW SYSTEM: Use VideoGroup
        if ($groupId) {
            $group = VideoGroup::find($groupId);
            if (! $group) {
                \Log::error("associateToMaster() FAILED - group {$groupId} not found");

                return false;
            }
        } else {
            // Get master's first group or create new one
            $group = $masterVideo->videoGroups()->first();

            if (! $group) {
                // Create new group for master
                $group = VideoGroup::create([
                    'name' => null,
                    'organization_id' => $masterVideo->organization_id,
                ]);

                // Attach master to group
                $group->videos()->attach($masterVideo->id, [
                    'is_master' => true,
                    'camera_angle' => 'Master / Tribuna Central',
                    'is_synced' => true,
                    'sync_offset' => 0,
                ]);

                \Log::info("Created new group {$group->id} for master video {$masterVideo->id}");
            }
        }

        // Check if this video is already in the group
        if ($this->isInGroup($group->id)) {
            \Log::warning("Video {$this->id} is already in group {$group->id}");
            // Update existing association
            $this->videoGroups()->updateExistingPivot($group->id, [
                'is_master' => false,
                'camera_angle' => $cameraAngle,
                'is_synced' => false,
                'sync_offset' => null,
            ]);
        } else {
            // Attach slave to group
            $this->videoGroups()->attach($group->id, [
                'is_master' => false,
                'camera_angle' => $cameraAngle,
                'is_synced' => false,
                'sync_offset' => null,
            ]);
        }

        \Log::info("associateToMaster() SUCCESS - Slave video {$this->id} associated to group {$group->id}");

        // Limpiar VideoGroups huérfanos del slave:
        // Si el slave tenía su propio grupo donde era el ÚNICO miembro (era master de sí mismo),
        // ese grupo queda sin sentido una vez que el slave se asocia a otro master.
        $orphanGroups = $this->videoGroups()
            ->wherePivot('is_master', true)
            ->where('video_groups.id', '!=', $group->id)
            ->get();

        foreach ($orphanGroups as $orphanGroup) {
            if ($orphanGroup->videos()->count() === 1) {
                \Log::info("Deleting orphaned VideoGroup {$orphanGroup->id} (slave video {$this->id} was its only member)");
                $orphanGroup->delete(); // Cascade elimina el pivot
            }
        }

        // Always mark slave as is_master=false on the videos table.
        // Uses direct DB query to bypass $fillable mass assignment restriction.
        \DB::table('videos')->where('id', $this->id)->update(['is_master' => false]);

        return true;
    }

    /**
     * Sync this slave video with the master in a specific group
     *
     * @param  float  $offset  Sync offset in seconds
     * @param  string|null  $referenceEvent  Reference event description
     * @param  int|null  $groupId  Specific group ID, if null uses first group
     */
    public function syncWithMaster(float $offset, ?string $referenceEvent = null, ?int $groupId = null): bool
    {
        \Log::info("syncWithMaster() - Video ID: {$this->id}, Group ID: {$groupId}, Offset: {$offset}");

        // NEW SYSTEM
        if ($groupId) {
            if (! $this->isInGroup($groupId)) {
                \Log::error("syncWithMaster() FAILED - video {$this->id} not in group {$groupId}");

                return false;
            }

            $this->videoGroups()->updateExistingPivot($groupId, [
                'sync_offset' => $offset,
                'is_synced' => true,
                'sync_reference_event' => $referenceEvent,
            ]);

            \Log::info("syncWithMaster() SUCCESS - Video {$this->id} synced in group {$groupId}");

            // FALLBACK: Also update old system
            if ($this->video_group_id) {
                $this->update([
                    'sync_offset' => $offset,
                    'is_synced' => true,
                    'sync_reference_event' => $referenceEvent,
                ]);
            }

            return true;
        }

        // Fallback to first group or old system
        $firstGroup = $this->videoGroups()->first();
        if ($firstGroup) {
            return $this->syncWithMaster($offset, $referenceEvent, $firstGroup->id);
        }

        // OLD SYSTEM fallback
        if (! $this->isSlave()) {
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
     * Remove this video from a specific group (or all groups if null)
     *
     * @param  int|null  $groupId  Specific group to remove from, null removes from all
     */
    public function removeFromGroup(?int $groupId = null): bool
    {
        \Log::info("removeFromGroup() - Video ID: {$this->id}, Group ID: ".($groupId ?? 'all'));

        // NEW SYSTEM
        if ($groupId) {
            if (! $this->isInGroup($groupId)) {
                \Log::warning("Video {$this->id} not in group {$groupId}");

                return false;
            }

            $this->videoGroups()->detach($groupId);
            \Log::info("Video {$this->id} removed from group {$groupId}");

            // If this was the last group, reset old system columns
            if ($this->videoGroups()->count() === 0) {
                $this->update([
                    'video_group_id' => null,
                    'is_master' => true,
                    'camera_angle' => null,
                    'sync_offset' => null,
                    'is_synced' => false,
                    'sync_reference_event' => null,
                ]);
            }

            return true;
        }

        // Remove from all groups
        $this->videoGroups()->detach();

        // OLD SYSTEM fallback
        $this->update([
            'video_group_id' => null,
            'is_master' => true,
            'camera_angle' => null,
            'sync_offset' => null,
            'is_synced' => false,
            'sync_reference_event' => null,
        ]);

        \Log::info("Video {$this->id} removed from all groups");

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
            'ala_izquierdo_forward', 'ala_derecho_forward',
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
