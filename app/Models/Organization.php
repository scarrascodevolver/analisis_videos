<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'is_active',
        'invitation_code',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generar código de invitación al crear organización
        static::creating(function ($organization) {
            if (empty($organization->invitation_code)) {
                $organization->invitation_code = self::generateUniqueInvitationCode();
            }
        });
    }

    /**
     * Genera un código de invitación único de 8 caracteres
     */
    public static function generateUniqueInvitationCode(): string
    {
        do {
            // Código de 8 caracteres: letras mayúsculas y números
            $code = strtoupper(Str::random(8));
            $exists = self::where('invitation_code', $code)->exists();
        } while ($exists);

        return $code;
    }

    /**
     * Regenera el código de invitación
     */
    public function regenerateInvitationCode(): string
    {
        $this->invitation_code = self::generateUniqueInvitationCode();
        $this->save();
        return $this->invitation_code;
    }

    /**
     * Scope para buscar por código de invitación
     */
    public function scopeByInvitationCode($query, string $code)
    {
        return $query->where('invitation_code', strtoupper($code));
    }

    /**
     * Buscar organización activa por código de invitación
     */
    public static function findByInvitationCode(string $code): ?self
    {
        return self::active()->byInvitationCode($code)->first();
    }

    /**
     * Usuarios que pertenecen a esta organización
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withPivot(['role', 'is_current', 'is_org_admin'])
                    ->withTimestamps();
    }

    /**
     * Videos de esta organización
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Equipos de esta organización
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Categorías de esta organización
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Evaluaciones de jugadores de esta organización
     */
    public function playerEvaluations(): HasMany
    {
        return $this->hasMany(PlayerEvaluation::class);
    }

    /**
     * Períodos de evaluación de esta organización
     */
    public function evaluationPeriods(): HasMany
    {
        return $this->hasMany(EvaluationPeriod::class);
    }

    /**
     * Configuraciones de esta organización
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * Scope para organizaciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtener URL del logo o placeholder
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }
        return asset('img/default-org-logo.png');
    }
}
