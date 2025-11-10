<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationPeriod extends Model
{
    protected $fillable = [
        'name',
        'description',
        'started_at',
        'ended_at',
        'is_active'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Relación con evaluaciones
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(PlayerEvaluation::class, 'evaluation_period_id');
    }

    /**
     * Obtener período activo
     */
    public static function getActive()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Activar este período (desactiva los demás)
     */
    public function activate()
    {
        // Desactivar todos los períodos
        self::where('is_active', true)->update(['is_active' => false]);

        // Activar este período
        $this->update(['is_active' => true, 'ended_at' => null]);
    }

    /**
     * Cerrar este período
     */
    public function close()
    {
        $this->update(['is_active' => false, 'ended_at' => now()]);
    }

    /**
     * Verificar si el período está abierto
     */
    public function isOpen(): bool
    {
        return $this->is_active && $this->ended_at === null;
    }
}
