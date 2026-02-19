<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    const TYPE_CLUB = 'club';

    const TYPE_ASOCIACION = 'asociacion';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'logo_path',
        'is_active',
        'onboarding_completed',
        'invitation_code',
        'timezone',
        'compression_strategy',
        'compression_start_hour',
        'compression_end_hour',
        'compression_hybrid_threshold',
        'bunny_library_id',
        'bunny_api_key',
        'bunny_cdn_hostname',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'onboarding_completed' => 'boolean',
            'compression_start_hour' => 'integer',
            'compression_end_hour' => 'integer',
            'compression_hybrid_threshold' => 'integer',
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
     * Clubes de esta organización (solo asociaciones)
     */
    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    public function isClub(): bool
    {
        return $this->type === self::TYPE_CLUB;
    }

    public function isAsociacion(): bool
    {
        return $this->type === self::TYPE_ASOCIACION;
    }

    /**
     * Categorías de esta organización
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Configuraciones de esta organización
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * Suscripciones de esta organización
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Pagos de esta organización
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Verificar si tiene suscripción activa
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    /**
     * Obtener suscripción activa
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();
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
            return asset('storage/'.$this->logo_path);
        }

        return asset('img/default-org-logo.png');
    }

    /**
     * Reglas de validacion para configuraciones de compresion
     */
    public static function compressionSettingsValidationRules(): array
    {
        return [
            'timezone' => 'required|string|timezone',
            'compression_strategy' => 'required|in:immediate,nocturnal,hybrid',
            'compression_start_hour' => 'required_unless:compression_strategy,immediate|nullable|integer|min:0|max:23',
            'compression_end_hour' => 'required_unless:compression_strategy,immediate|nullable|integer|min:0|max:23|gt:compression_start_hour',
            'compression_hybrid_threshold' => 'required_if:compression_strategy,hybrid|nullable|integer|min:100|max:10000',
        ];
    }
}
