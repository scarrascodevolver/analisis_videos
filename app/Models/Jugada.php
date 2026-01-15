<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Jugada extends Model
{
    use BelongsToOrganization;

    protected $table = 'jugadas';

    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'category',
        'data',
        'thumbnail',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Usuario que creó la jugada
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Organización a la que pertenece
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Icono de categoría
     */
    public function getCategoryIconAttribute(): string
    {
        return match($this->category) {
            'forwards' => '🟣',
            'backs' => '🟢',
            'full_team' => '⚪',
            default => '⚪',
        };
    }
}
