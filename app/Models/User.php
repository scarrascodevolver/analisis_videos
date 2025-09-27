<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
