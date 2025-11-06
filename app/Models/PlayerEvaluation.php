<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerEvaluation extends Model
{
    protected $fillable = [
        'evaluator_id',
        'evaluated_player_id',
        // Acondicionamiento Físico
        'resistencia',
        'velocidad',
        'musculatura',
        // Destrezas Básicas
        'recepcion_pelota',
        'pase_dos_lados',
        'juego_aereo',
        'tackle',
        'ruck',
        'duelos',
        'carreras',
        'conocimiento_plan',
        'entendimiento_juego',
        'reglamento',
        // Destrezas Mentales
        'autocontrol',
        'concentracion',
        'toma_decisiones',
        'liderazgo',
        // Otros Aspectos
        'disciplina',
        'compromiso',
        'puntualidad',
        'actitud_positiva',
        'actitud_negativa',
        'comunicacion',
        // Habilidades Forwards
        'scrum_tecnica',
        'scrum_empuje',
        'line_levantar',
        'line_saltar',
        'line_lanzamiento',
        // Habilidades Backs
        'kick_salidas',
        'kick_aire',
        'kick_rastron',
        'kick_palos',
        'kick_drop',
        // Puntaje
        'total_score',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
    ];

    /**
     * Relación con el usuario que evalúa
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Relación con el jugador evaluado
     */
    public function evaluatedPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_player_id');
    }

    /**
     * Calcular el puntaje total
     * Fórmula: Suma de todos los campos - actitud_negativa
     */
    public function calculateTotalScore(): float
    {
        $scores = [
            // Acondicionamiento Físico
            $this->resistencia,
            $this->velocidad,
            $this->musculatura,
            // Destrezas Básicas
            $this->recepcion_pelota,
            $this->pase_dos_lados,
            $this->juego_aereo,
            $this->tackle,
            $this->ruck,
            $this->duelos,
            $this->carreras,
            $this->conocimiento_plan,
            $this->entendimiento_juego,
            $this->reglamento,
            // Destrezas Mentales
            $this->autocontrol,
            $this->concentracion,
            $this->toma_decisiones,
            $this->liderazgo,
            // Otros Aspectos
            $this->disciplina,
            $this->compromiso,
            $this->puntualidad,
            $this->actitud_positiva,
            $this->comunicacion,
        ];

        // Agregar habilidades específicas si existen
        if ($this->scrum_tecnica !== null) {
            $scores[] = $this->scrum_tecnica;
            $scores[] = $this->scrum_empuje;
            $scores[] = $this->line_levantar;
            $scores[] = $this->line_saltar;
            $scores[] = $this->line_lanzamiento;
        }

        if ($this->kick_salidas !== null) {
            $scores[] = $this->kick_salidas;
            $scores[] = $this->kick_aire;
            $scores[] = $this->kick_rastron;
            $scores[] = $this->kick_palos;
            $scores[] = $this->kick_drop;
        }

        $total = array_sum($scores);

        // Restar actitud negativa
        $total -= $this->actitud_negativa;

        // Calcular promedio sobre 10
        $numFields = count($scores);
        $average = $numFields > 0 ? ($total / $numFields) : 0;

        return round($average, 2);
    }

    /**
     * Hook antes de guardar para calcular el score
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            $evaluation->total_score = $evaluation->calculateTotalScore();
        });
    }
}
