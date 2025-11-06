<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PlayerEvaluation;

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

        // Obtener evaluaciones ya realizadas por el usuario actual
        $evaluatedPlayerIds = PlayerEvaluation::where('evaluator_id', $currentUser->id)
            ->pluck('evaluated_player_id')
            ->toArray();

        // Separar en forwards y backs
        $forwards = $players->filter(function($player) use ($forwardsPositions) {
            $position = $player->profile->position ?? '';
            return in_array($position, $forwardsPositions);
        })->values()->map(function($player) use ($evaluatedPlayerIds) {
            $player->evaluated = in_array($player->id, $evaluatedPlayerIds);
            return $player;
        });

        $backs = $players->filter(function($player) use ($backsPositions) {
            $position = $player->profile->position ?? '';
            return in_array($position, $backsPositions);
        })->values()->map(function($player) use ($evaluatedPlayerIds) {
            $player->evaluated = in_array($player->id, $evaluatedPlayerIds);
            return $player;
        });

        // Calcular progreso real
        $forwardsProgress = $forwards->where('evaluated', true)->count();
        $backsProgress = $backs->where('evaluated', true)->count();

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
     * Guardar evaluación
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();

        // Validar datos
        $validated = $request->validate([
            'evaluated_player_id' => 'required|exists:users,id',
            // Acondicionamiento Físico
            'resistencia' => 'required|integer|min:0|max:10',
            'velocidad' => 'required|integer|min:0|max:10',
            'musculatura' => 'required|integer|min:0|max:10',
            // Destrezas Básicas
            'recepcion_pelota' => 'required|integer|min:0|max:10',
            'pase_dos_lados' => 'required|integer|min:0|max:10',
            'juego_aereo' => 'required|integer|min:0|max:10',
            'tackle' => 'required|integer|min:0|max:10',
            'ruck' => 'required|integer|min:0|max:10',
            'duelos' => 'required|integer|min:0|max:10',
            'carreras' => 'required|integer|min:0|max:10',
            'conocimiento_plan' => 'required|integer|min:0|max:10',
            'entendimiento_juego' => 'required|integer|min:0|max:10',
            'reglamento' => 'required|integer|min:0|max:10',
            // Destrezas Mentales
            'autocontrol' => 'required|integer|min:0|max:10',
            'concentracion' => 'required|integer|min:0|max:10',
            'toma_decisiones' => 'required|integer|min:0|max:10',
            'liderazgo' => 'required|integer|min:0|max:10',
            // Otros Aspectos
            'disciplina' => 'required|integer|min:0|max:10',
            'compromiso' => 'required|integer|min:0|max:10',
            'puntualidad' => 'required|integer|min:0|max:10',
            'actitud_positiva' => 'required|integer|min:0|max:10',
            'actitud_negativa' => 'required|integer|min:0|max:10',
            'comunicacion' => 'required|integer|min:0|max:10',
            // Habilidades específicas (opcionales)
            'scrum_tecnica' => 'nullable|integer|min:0|max:10',
            'scrum_empuje' => 'nullable|integer|min:0|max:10',
            'line_levantar' => 'nullable|integer|min:0|max:10',
            'line_saltar' => 'nullable|integer|min:0|max:10',
            'line_lanzamiento' => 'nullable|integer|min:0|max:10',
            'kick_salidas' => 'nullable|integer|min:0|max:10',
            'kick_aire' => 'nullable|integer|min:0|max:10',
            'kick_rastron' => 'nullable|integer|min:0|max:10',
            'kick_palos' => 'nullable|integer|min:0|max:10',
            'kick_drop' => 'nullable|integer|min:0|max:10',
        ]);

        // Validar que no se evalúe a sí mismo
        if ($validated['evaluated_player_id'] == $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes evaluarte a ti mismo.'
            ], 403);
        }

        // Validar que sea de la misma categoría
        $evaluatedPlayer = User::with('profile')->findOrFail($validated['evaluated_player_id']);
        $categoryId = $currentUser->profile->user_category_id ?? null;

        if ($evaluatedPlayer->profile->user_category_id !== $categoryId) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes evaluar jugadores de tu misma categoría.'
            ], 403);
        }

        // Verificar si ya evaluó a este jugador
        $existingEvaluation = PlayerEvaluation::where('evaluator_id', $currentUser->id)
            ->where('evaluated_player_id', $validated['evaluated_player_id'])
            ->first();

        if ($existingEvaluation) {
            return response()->json([
                'success' => false,
                'message' => 'Ya evaluaste a este jugador.'
            ], 409);
        }

        // Crear evaluación
        $validated['evaluator_id'] = $currentUser->id;
        $evaluation = PlayerEvaluation::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Evaluación guardada exitosamente.',
            'evaluation_id' => $evaluation->id,
            'total_score' => $evaluation->total_score
        ]);
    }

    /**
     * Dashboard de resultados para entrenadores y jugadores
     */
    public function dashboard()
    {
        $currentUser = auth()->user();

        // Si es jugador, mostrar solo sus propios resultados
        if ($currentUser->role === 'jugador') {
            // Obtener solo el jugador actual
            $players = User::where('id', $currentUser->id)
                ->with(['profile', 'receivedEvaluations'])
                ->get();

            // Obtener jugadores de su categoría para calcular total posible
            $categoryId = $currentUser->profile->user_category_id ?? null;
            $totalInCategory = User::where('role', 'jugador')
                ->where('id', '!=', $currentUser->id)
                ->whereHas('profile', function($q) use ($categoryId) {
                    if ($categoryId) {
                        $q->where('user_category_id', $categoryId);
                    }
                })
                ->count();

            // Calcular estadísticas del jugador actual
            $playersStats = $players->map(function($player) use ($totalInCategory) {
                $evaluations = $player->receivedEvaluations;

                return [
                    'player' => $player,
                    'average_score' => $evaluations->avg('total_score') ?? 0,
                    'evaluations_count' => $evaluations->count(),
                    'total_possible' => $totalInCategory,
                    'completion_percentage' => $totalInCategory > 0
                        ? round(($evaluations->count() / $totalInCategory) * 100, 1)
                        : 0,
                ];
            });

            $categories = collect(); // No mostrar filtros para jugadores
            $categoryId = null;

            return view('evaluations.dashboard', compact('playersStats', 'categories', 'categoryId'));
        }

        // Para entrenadores/analistas: mostrar todos los resultados
        // Obtener todas las categorías para el filtro
        $categories = \App\Models\Category::all();

        // Filtro de categoría (por defecto, la del entrenador)
        $categoryId = request('category_id', $currentUser->profile->user_category_id ?? null);

        // Obtener jugadores de la categoría con sus evaluaciones
        $players = User::where('role', 'jugador')
            ->whereHas('profile', function($q) use ($categoryId) {
                if ($categoryId) {
                    $q->where('user_category_id', $categoryId);
                }
            })
            ->with(['profile', 'receivedEvaluations'])
            ->get();

        // Calcular estadísticas para cada jugador
        $playersStats = $players->map(function($player) use ($players) {
            $evaluations = $player->receivedEvaluations;
            $totalEvaluators = $players->where('id', '!=', $player->id)->count();

            return [
                'player' => $player,
                'average_score' => $evaluations->avg('total_score') ?? 0,
                'evaluations_count' => $evaluations->count(),
                'total_possible' => $totalEvaluators,
                'completion_percentage' => $totalEvaluators > 0
                    ? round(($evaluations->count() / $totalEvaluators) * 100, 1)
                    : 0,
            ];
        });

        // Ordenar por promedio descendente
        $playersStats = $playersStats->sortByDesc('average_score')->values();

        return view('evaluations.dashboard', compact('playersStats', 'categories', 'categoryId'));
    }

    /**
     * Mostrar detalle de evaluación individual
     */
    public function show($playerId)
    {
        $currentUser = auth()->user();

        // Jugadores solo pueden ver su propio detalle
        if ($currentUser->role === 'jugador' && $playerId != $currentUser->id) {
            return redirect()->route('evaluations.dashboard')->with('error', 'Solo puedes ver tus propios resultados.');
        }

        // Staff (entrenadores/analistas) pueden ver cualquier detalle
        if (!in_array($currentUser->role, ['entrenador', 'analista', 'jugador'])) {
            return redirect()->route('dashboard')->with('error', 'No tienes permisos para ver esta página.');
        }

        // Obtener el jugador evaluado
        $player = User::with('profile.category')->findOrFail($playerId);

        // Obtener todas las evaluaciones del jugador
        $evaluations = PlayerEvaluation::where('evaluated_player_id', $playerId)
            ->with(['evaluator.profile'])
            ->get();

        if ($evaluations->isEmpty()) {
            return redirect()->route('evaluations.dashboard')->with('error', 'Este jugador no tiene evaluaciones aún.');
        }

        // Calcular promedios por categoría
        $averages = [
            'acondicionamiento' => [
                'resistencia' => $evaluations->avg('resistencia'),
                'velocidad' => $evaluations->avg('velocidad'),
                'musculatura' => $evaluations->avg('musculatura'),
            ],
            'destrezas_basicas' => [
                'recepcion_pelota' => $evaluations->avg('recepcion_pelota'),
                'pase_dos_lados' => $evaluations->avg('pase_dos_lados'),
                'juego_aereo' => $evaluations->avg('juego_aereo'),
                'tackle' => $evaluations->avg('tackle'),
                'ruck' => $evaluations->avg('ruck'),
                'duelos' => $evaluations->avg('duelos'),
                'carreras' => $evaluations->avg('carreras'),
                'conocimiento_plan' => $evaluations->avg('conocimiento_plan'),
                'entendimiento_juego' => $evaluations->avg('entendimiento_juego'),
                'reglamento' => $evaluations->avg('reglamento'),
            ],
            'destrezas_mentales' => [
                'autocontrol' => $evaluations->avg('autocontrol'),
                'concentracion' => $evaluations->avg('concentracion'),
                'toma_decisiones' => $evaluations->avg('toma_decisiones'),
                'liderazgo' => $evaluations->avg('liderazgo'),
            ],
            'otros_aspectos' => [
                'disciplina' => $evaluations->avg('disciplina'),
                'compromiso' => $evaluations->avg('compromiso'),
                'puntualidad' => $evaluations->avg('puntualidad'),
                'actitud_positiva' => $evaluations->avg('actitud_positiva'),
                'actitud_negativa' => $evaluations->avg('actitud_negativa'),
                'comunicacion' => $evaluations->avg('comunicacion'),
            ],
        ];

        // Determinar si es Forward o Back y agregar habilidades específicas
        $forwardsPositions = [
            'Pilar Izquierdo', 'Hooker', 'Pilar Derecho',
            'Segunda Línea', 'Ala', 'Número 8'
        ];
        $playerPosition = $player->profile->position ?? '';
        $isForward = in_array($playerPosition, $forwardsPositions);

        if ($isForward) {
            $averages['habilidades_forwards'] = [
                'scrum_tecnica' => $evaluations->avg('scrum_tecnica'),
                'scrum_empuje' => $evaluations->avg('scrum_empuje'),
                'line_levantar' => $evaluations->avg('line_levantar'),
                'line_saltar' => $evaluations->avg('line_saltar'),
                'line_lanzamiento' => $evaluations->avg('line_lanzamiento'),
            ];
        } else {
            $averages['habilidades_backs'] = [
                'kick_salidas' => $evaluations->avg('kick_salidas'),
                'kick_aire' => $evaluations->avg('kick_aire'),
                'kick_rastron' => $evaluations->avg('kick_rastron'),
                'kick_palos' => $evaluations->avg('kick_palos'),
                'kick_drop' => $evaluations->avg('kick_drop'),
            ];
        }

        // Calcular promedio total
        $totalScore = $evaluations->avg('total_score');
        $evaluationCount = $evaluations->count();

        return view('evaluations.show', compact('player', 'evaluations', 'averages', 'totalScore', 'evaluationCount', 'isForward'));
    }
}
