<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class EvaluationController extends Controller
{
    /**
     * Mostrar lista de jugadores para evaluar (con tabs Forwards/Backs)
     */
    public function index()
    {
        $currentUser = auth()->user();
        $categoryId = $currentUser->profile->user_category_id ?? null;

        if (!$categoryId) {
            return redirect()->route('dashboard')->with('error', 'No tienes una categoría asignada.');
        }

        // Obtener jugadores de la misma categoría (excepto el usuario actual)
        $players = User::whereHas('profile', function($q) use ($categoryId) {
            $q->where('user_category_id', $categoryId);
        })
        ->where('id', '!=', $currentUser->id)
        ->where('role', 'jugador')
        ->with('profile')
        ->get();

        // Posiciones de Forwards
        $forwardsPositions = [
            'Pilar Izquierdo',
            'Hooker',
            'Pilar Derecho',
            'Segunda Línea',
            'Ala',
            'Número 8'
        ];

        // Posiciones de Backs
        $backsPositions = [
            'Medio Scrum',
            'Apertura',
            'Centro',
            'Wing',
            'Fullback'
        ];

        // Separar en forwards y backs
        $forwards = $players->filter(function($player) use ($forwardsPositions) {
            $position = $player->profile->position ?? '';
            return in_array($position, $forwardsPositions);
        })->values();

        $backs = $players->filter(function($player) use ($backsPositions) {
            $position = $player->profile->position ?? '';
            return in_array($position, $backsPositions);
        })->values();

        // TODO: Calcular progreso cuando implementemos backend
        $forwardsProgress = 0;
        $backsProgress = 0;

        return view('evaluations.index', compact('forwards', 'backs', 'forwardsProgress', 'backsProgress'));
    }

    /**
     * Mostrar wizard de evaluación para un jugador específico
     */
    public function wizard($playerId)
    {
        $currentUser = auth()->user();
        $categoryId = $currentUser->profile->user_category_id ?? null;

        // Obtener el jugador a evaluar
        $player = User::with('profile')->findOrFail($playerId);

        // Validaciones de seguridad
        if ($player->id === $currentUser->id) {
            return redirect()->route('evaluations.index')->with('error', 'No puedes evaluarte a ti mismo.');
        }

        if ($player->profile->user_category_id !== $categoryId) {
            return redirect()->route('evaluations.index')->with('error', 'Solo puedes evaluar jugadores de tu misma categoría.');
        }

        // Determinar si es Forward o Back
        $forwardsPositions = [
            'Pilar Izquierdo',
            'Hooker',
            'Pilar Derecho',
            'Segunda Línea',
            'Ala',
            'Número 8'
        ];

        $playerPosition = $player->profile->position ?? '';
        $isForward = in_array($playerPosition, $forwardsPositions);

        return view('evaluations.wizard', compact('player', 'isForward'));
    }

    /**
     * Guardar evaluación (Fase 2 - Backend)
     */
    public function store(Request $request)
    {
        // TODO: Implementar en Fase 2
        return redirect()->route('evaluations.success');
    }
}
