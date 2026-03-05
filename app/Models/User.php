<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Available roles in the system
     */
    public const ROLES = [
        'jugador',
        'entrenador',
        'analista',
        'staff',
        'super_admin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_super_admin',
        'is_org_manager',
        'default_organization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_org_manager' => 'boolean',
        ];
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function uploadedVideos()
    {
        return $this->hasMany(Video::class, 'uploaded_by');
    }

    public function videoComments()
    {
        return $this->hasMany(VideoComment::class);
    }

    public function assignedVideos()
    {
        return $this->hasMany(VideoAssignment::class, 'assigned_to');
    }

    public function assignedByMe()
    {
        return $this->hasMany(VideoAssignment::class, 'assigned_by');
    }

    public function videoAnnotations()
    {
        return $this->hasMany(VideoAnnotation::class);
    }

    public function pendingAssignments()
    {
        // Solo contar asignaciones cuyos videos existen (no fueron eliminados)
        return $this->assignedVideos()->whereHas('video');
    }

    public function isAnalyst()
    {
        return $this->role === 'analista';
    }

    public function isPlayer()
    {
        return $this->role === 'jugador';
    }

    public function isCoach()
    {
        return $this->role === 'entrenador';
    }

    public function isSuperAdmin()
    {
        return $this->is_super_admin === true;
    }

    public function isOrgManager()
    {
        return $this->is_org_manager === true;
    }

    /**
     * Organizaciones a las que pertenece el usuario
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'is_current', 'is_org_admin'])
            ->withTimestamps();
    }

    public function defaultOrganization()
    {
        return $this->belongsTo(Organization::class, 'default_organization_id');
    }

    /**
     * Verificar si el usuario es administrador de la organización actual
     */
    public function isOrgAdmin(): bool
    {
        $org = $this->currentOrganization();

        return $org ? (bool) $org->pivot->is_org_admin : false;
    }

    /**
     * Obtener la organización actualmente seleccionada
     */
    public function currentOrganization()
    {
        // 1) Session context (primary source of truth)
        $sessionOrgId = $this->sessionOrganizationId();
        if ($sessionOrgId) {
            $org = $this->organizations()->where('organizations.id', $sessionOrgId)->first();
            if ($org) {
                return $org;
            }
        }

        // 2) Stable default organization (persistent preference)
        if ($this->default_organization_id) {
            $org = $this->organizations()->where('organizations.id', $this->default_organization_id)->first();
            if ($org) {
                $this->storeSessionOrganizationId($org->id);

                return $org;
            }
        }

        // 3) Legacy fallback for old records
        $legacyOrg = $this->organizations()
            ->wherePivot('is_current', true)
            ->first();

        if ($legacyOrg) {
            $this->storeSessionOrganizationId($legacyOrg->id);

            if (! $this->default_organization_id) {
                $this->forceFill(['default_organization_id' => $legacyOrg->id])->save();
            }
        }

        return $legacyOrg;
    }

    /**
     * Cambiar a una organización específica
     */
    public function switchOrganization(Organization $organization, bool $isSuperAdmin = false, array $context = []): bool
    {
        $fromOrgId = $this->currentOrganization()?->id;
        $belongsToOrg = $this->organizations()->where('organizations.id', $organization->id)->exists();

        // Verificar que el usuario pertenece a esta organización (excepto super admins)
        if (! $belongsToOrg && ! $isSuperAdmin) {
            return false;
        }

        // Desmarcar todas las organizaciones como current
        $this->organizations()->updateExistingPivot(
            $this->organizations()->pluck('organizations.id')->toArray(),
            ['is_current' => false]
        );

        // Si es super admin/org manager y no pertenece a la org, agregar sin duplicar
        if ($isSuperAdmin && ! $belongsToOrg) {
            \DB::table('organization_user')->updateOrInsert(
                ['user_id' => $this->id, 'organization_id' => $organization->id],
                ['role' => $this->role ?? 'analista', 'is_current' => true, 'is_org_admin' => true]
            );
        } else {
            // Marcar la nueva organización como current
            $this->organizations()->updateExistingPivot($organization->id, ['is_current' => true]);
        }

        // Nuevo contexto primario: sesión
        $this->storeSessionOrganizationId($organization->id);

        // Persistir org por defecto solo cuando aún no existe
        if (! $this->default_organization_id) {
            $this->forceFill(['default_organization_id' => $organization->id])->save();
        }

        // Auditoría de cambios de organización
        if ($fromOrgId !== $organization->id) {
            $request = app()->bound('request') ? request() : null;

            try {
                OrganizationSwitchAudit::create([
                    'user_id' => $this->id,
                    'from_organization_id' => $fromOrgId,
                    'to_organization_id' => $organization->id,
                    'ip_address' => $context['ip_address'] ?? $request?->ip(),
                    'user_agent' => $context['user_agent'] ?? $request?->userAgent(),
                    'source_url' => $context['source_url'] ?? $request?->headers->get('referer'),
                    'switch_reason' => $context['switch_reason'] ?? 'manual',
                    'switched_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return true;
    }

    /**
     * Obtener el rol del usuario en la organización actual
     */
    public function currentOrganizationRole(): ?string
    {
        $org = $this->currentOrganization();

        return $org ? $org->pivot->role : null;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    private function sessionOrganizationId(): ?int
    {
        if (! $this->canUseSessionContext()) {
            return null;
        }

        $value = session('current_organization_id');

        return $value ? (int) $value : null;
    }

    private function storeSessionOrganizationId(int $organizationId): void
    {
        if (! $this->canUseSessionContext()) {
            return;
        }

        session(['current_organization_id' => $organizationId]);
    }

    private function canUseSessionContext(): bool
    {
        return ! app()->runningInConsole() && app()->bound('session');
    }
}
