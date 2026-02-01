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
        return $this->assignedVideos(); // Ya no hay estados, todas las asignaciones están "activas"
    }

    public function receivedEvaluations()
    {
        return $this->hasMany(PlayerEvaluation::class, 'evaluated_player_id');
    }

    public function givenEvaluations()
    {
        return $this->hasMany(PlayerEvaluation::class, 'evaluator_id');
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

    /**
     * Organizaciones a las que pertenece el usuario
     */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'is_current', 'is_org_admin'])
            ->withTimestamps();
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
        return $this->organizations()
            ->wherePivot('is_current', true)
            ->first();
    }

    /**
     * Cambiar a una organización específica
     */
    public function switchOrganization(Organization $organization, bool $isSuperAdmin = false): bool
    {
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

        // Si es super admin y no pertenece a la org, agregarlo temporalmente
        if ($isSuperAdmin && ! $belongsToOrg) {
            $this->organizations()->attach($organization->id, [
                'role' => $this->role ?? 'analista',
                'is_current' => true,
                'is_org_admin' => true,
            ]);
        } else {
            // Marcar la nueva organización como current
            $this->organizations()->updateExistingPivot($organization->id, ['is_current' => true]);
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
}
