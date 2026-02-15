<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClipCategory extends Model
{
    use BelongsToOrganization;

    /**
     * Scope types for clip categories
     */
    public const SCOPE_ORGANIZATION = 'organization'; // Plantillas del club - todos ven
    public const SCOPE_USER = 'user';                  // Personales - solo el usuario
    public const SCOPE_VIDEO = 'video';                // Del video/XML - solo en ese video

    protected $fillable = [
        'organization_id',
        'scope',
        'user_id',
        'video_id',
        'name',
        'slug',
        'color',
        'icon',
        'hotkey',
        'lead_seconds',
        'lag_seconds',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'lead_seconds' => 'integer',
            'lag_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }

            // Default scope is organization
            if (empty($category->scope)) {
                $category->scope = self::SCOPE_ORGANIZATION;
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function clips()
    {
        return $this->hasMany(VideoClip::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // ==================== QUERY SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get organization-level categories (plantillas)
     */
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('scope', self::SCOPE_ORGANIZATION)
            ->where('organization_id', $organizationId);
    }

    /**
     * Get user-level categories (personales)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('scope', self::SCOPE_USER)
            ->where('user_id', $userId);
    }

    /**
     * Get video-level categories (del XML)
     */
    public function scopeForVideo($query, int $videoId)
    {
        return $query->where('scope', self::SCOPE_VIDEO)
            ->where('video_id', $videoId);
    }

    /**
     * Get all categories visible for a specific context (org + user + video)
     * This is the main method to get the complete category list for a video player
     */
    public function scopeForContext($query, int $organizationId, int $userId, ?int $videoId = null)
    {
        return $query->where(function ($q) use ($organizationId, $userId, $videoId) {
            // Plantillas del club
            $q->where(function ($q) use ($organizationId) {
                $q->where('scope', self::SCOPE_ORGANIZATION)
                    ->where('organization_id', $organizationId);
            })
            // Personales del usuario
            ->orWhere(function ($q) use ($userId) {
                $q->where('scope', self::SCOPE_USER)
                    ->where('user_id', $userId);
            });

            // Del video (si se proporciona)
            if ($videoId) {
                $q->orWhere(function ($q) use ($videoId) {
                    $q->where('scope', self::SCOPE_VIDEO)
                        ->where('video_id', $videoId);
                });
            }
        });
    }

    /**
     * Get only organization templates (for admin listing)
     */
    public function scopeTemplates($query)
    {
        return $query->where('scope', self::SCOPE_ORGANIZATION);
    }

    /**
     * Get only personal categories (for user's own listing)
     */
    public function scopePersonal($query)
    {
        return $query->where('scope', self::SCOPE_USER);
    }

    /**
     * Get only video-specific categories
     */
    public function scopeVideoSpecific($query)
    {
        return $query->where('scope', self::SCOPE_VIDEO);
    }

    // ==================== HELPERS ====================

    /**
     * Check if this is an organization template
     */
    public function isTemplate(): bool
    {
        return $this->scope === self::SCOPE_ORGANIZATION;
    }

    /**
     * Check if this is a personal category
     */
    public function isPersonal(): bool
    {
        return $this->scope === self::SCOPE_USER;
    }

    /**
     * Check if this is a video-specific category
     */
    public function isVideoSpecific(): bool
    {
        return $this->scope === self::SCOPE_VIDEO;
    }

    /**
     * Get a label for the scope (for UI)
     */
    public function getScopeLabelAttribute(): string
    {
        return match ($this->scope) {
            self::SCOPE_ORGANIZATION => 'Plantilla del club',
            self::SCOPE_USER => 'Personal',
            self::SCOPE_VIDEO => 'De este video',
            default => 'Desconocido',
        };
    }
}
