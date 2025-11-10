<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PlayerEvaluation;
use App\Models\Setting;
use App\Models\EvaluationPeriod;

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

        // Verificar si hay un período activo
        $activePeriod = EvaluationPeriod::getActive();

        if (!$activePeriod || !$activePeriod->isOpen()) {
            return redirect()->route('dashboard')->with('warning', 'No hay un período de evaluación activo actualmente. Consulta con tu entrenador.');
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

        // Obtener evaluaciones ya realizadas EN EL PERÍODO ACTIVO
        $evaluatedPlayerIds = PlayerEvaluation::where('evaluator_id', $currentUser->id)
            ->where('evaluation_period_id', $activePeriod->id)
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

        // Verificar si las evaluaciones están habilitadas (solo para jugadores)
        if ($currentUser->role === 'jugador' && !Setting::areEvaluationsEnabled()) {
            return redirect()->route('evaluations.index')->with('error', 'Las evaluaciones están deshabilitadas actualmente.');
        }

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

        // Verificar si las evaluaciones están habilitadas (solo para jugadores)
        if ($currentUser->role === 'jugador' && !Setting::areEvaluationsEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Las evaluaciones están deshabilitadas actualmente.'
            ], 403);
        }

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

        // Obtener período activo
        $activePeriod = EvaluationPeriod::getActive();

        if (!$activePeriod) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un período de evaluación activo.'
            ], 403);
        }

        // Verificar si ya evaluó a este jugador EN ESTE PERÍODO
        $existingEvaluation = PlayerEvaluation::where('evaluator_id', $currentUser->id)
            ->where('evaluated_player_id', $validated['evaluated_player_id'])
            ->where('evaluation_period_id', $activePeriod->id)
            ->first();

        if ($existingEvaluation) {
            return response()->json([
                'success' => false,
                'message' => 'Ya evaluaste a este jugador en el período actual.'
            ], 409);
        }

        // Crear evaluación con el período activo
        $validated['evaluator_id'] = $currentUser->id;
        $validated['evaluation_period_id'] = $activePeriod->id;
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

        // Obtener todos los períodos disponibles para el selector
        $allPeriods = EvaluationPeriod::orderBy('started_at', 'desc')->get();

        // Filtro de período (por defecto, el activo)
        $periodId = request('period_id');

        if ($periodId === 'all') {
            // Ver todos los períodos
            $selectedPeriod = null;
        } elseif ($periodId) {
            // Ver un período específico
            $selectedPeriod = EvaluationPeriod::find($periodId);
        } else {
            // Por defecto, ver el período activo
            $selectedPeriod = EvaluationPeriod::getActive();
            $periodId = $selectedPeriod ? $selectedPeriod->id : null;
        }

        // Si es jugador, mostrar solo sus propios resultados
        if ($currentUser->role === 'jugador') {
            // Obtener solo el jugador actual
            $players = User::where('id', $currentUser->id)
                ->with(['profile'])
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

            // Calcular estadísticas del jugador actual con filtro de período
            $playersStats = $players->map(function($player) use ($totalInCategory, $periodId) {
                // Filtrar evaluaciones por período
                $evaluationsQuery = $player->receivedEvaluations();

                if ($periodId && $periodId !== 'all') {
                    $evaluationsQuery->where('evaluation_period_id', $periodId);
                }

                $evaluations = $evaluationsQuery->get();

                // Calcular puntaje total promedio
                $totalPointsSum = 0;
                $maxPossible = 280; // Máximo fijo para todos

                if ($evaluations->count() > 0) {
                    foreach ($evaluations as $eval) {
                        $totalPointsSum += $eval->getTotalPoints();
                    }
                    $avgTotalPoints = round($totalPointsSum / $evaluations->count(), 0);
                    $totalPointsPercentage = round(($avgTotalPoints / $maxPossible) * 100, 1);
                } else {
                    $avgTotalPoints = 0;
                    $totalPointsPercentage = 0;
                }

                return [
                    'player' => $player,
                    'average_score' => $evaluations->avg('total_score') ?? 0,
                    'total_points_avg' => $avgTotalPoints,
                    'total_points_max' => $maxPossible,
                    'total_points_percentage' => $totalPointsPercentage,
                    'evaluations_count' => $evaluations->count(),
                    'total_possible' => $totalInCategory,
                    'completion_percentage' => $totalInCategory > 0
                        ? round(($evaluations->count() / $totalInCategory) * 100, 1)
                        : 0,
                ];
            });

            $categories = collect(); // No mostrar filtros para jugadores
            $categoryId = null;

            return view('evaluations.dashboard', compact('playersStats', 'categories', 'categoryId', 'allPeriods', 'periodId', 'selectedPeriod'));
        }

        // Para entrenadores/analistas: mostrar todos los resultados
        // Obtener todas las categorías para el filtro
        $categories = \App\Models\Category::all();

        // Filtro de categoría (por defecto, la del entrenador)
        $categoryId = request('category_id', $currentUser->profile->user_category_id ?? null);

        // Obtener jugadores de la categoría
        $players = User::where('role', 'jugador')
            ->whereHas('profile', function($q) use ($categoryId) {
                if ($categoryId) {
                    $q->where('user_category_id', $categoryId);
                }
            })
            ->with(['profile'])
            ->get();

        // Calcular estadísticas para cada jugador con filtro de período
        $playersStats = $players->map(function($player) use ($players, $periodId) {
            // Filtrar evaluaciones por período
            $evaluationsQuery = $player->receivedEvaluations();

            if ($periodId && $periodId !== 'all') {
                $evaluationsQuery->where('evaluation_period_id', $periodId);
            }

            $evaluations = $evaluationsQuery->get();
            $totalEvaluators = $players->where('id', '!=', $player->id)->count();

            // Calcular puntaje total promedio
            $totalPointsSum = 0;
            $maxPossible = 280; // Máximo fijo para todos

            if ($evaluations->count() > 0) {
                foreach ($evaluations as $eval) {
                    $totalPointsSum += $eval->getTotalPoints();
                }
                $avgTotalPoints = round($totalPointsSum / $evaluations->count(), 0);
                $totalPointsPercentage = round(($avgTotalPoints / $maxPossible) * 100, 1);
            } else {
                $avgTotalPoints = 0;
                $totalPointsPercentage = 0;
            }

            return [
                'player' => $player,
                'average_score' => $evaluations->avg('total_score') ?? 0,
                'total_points_avg' => $avgTotalPoints,
                'total_points_max' => $maxPossible,
                'total_points_percentage' => $totalPointsPercentage,
                'evaluations_count' => $evaluations->count(),
                'total_possible' => $totalEvaluators,
                'completion_percentage' => $totalEvaluators > 0
                    ? round(($evaluations->count() / $totalEvaluators) * 100, 1)
                    : 0,
            ];
        });

        // Ordenar: Primero los evaluados (por puntaje total desc), luego los sin evaluar
        $withEvaluations = $playersStats->filter(fn($stat) => $stat['evaluations_count'] > 0)
            ->sortByDesc('total_points_avg');
        $withoutEvaluations = $playersStats->filter(fn($stat) => $stat['evaluations_count'] == 0);

        $playersStats = $withEvaluations->merge($withoutEvaluations)->values();

        return view('evaluations.dashboard', compact('playersStats', 'categories', 'categoryId', 'allPeriods', 'periodId', 'selectedPeriod'));
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

    /**
     * Alternar habilitación de evaluaciones (solo entrenadores/analistas)
     * Ahora gestiona períodos automáticamente
     */
    public function toggleEvaluations(Request $request)
    {
        $currentUser = auth()->user();

        // Solo entrenadores y analistas pueden toggle
        if (!in_array($currentUser->role, ['entrenador', 'analista'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        // Si es GET, retornar estado actual basado en período activo
        if ($request->isMethod('get')) {
            $activePeriod = EvaluationPeriod::getActive();
            $isEnabled = $activePeriod && $activePeriod->isOpen();

            return response()->json([
                'success' => true,
                'enabled' => $isEnabled,
                'period' => $activePeriod ? [
                    'id' => $activePeriod->id,
                    'name' => $activePeriod->name,
                    'started_at' => $activePeriod->started_at->format('d/m/Y H:i')
                ] : null
            ]);
        }

        // Si es POST, gestionar períodos
        $activePeriod = EvaluationPeriod::getActive();

        if ($activePeriod && $activePeriod->isOpen()) {
            // DESHABILITAR: Cerrar período actual
            $activePeriod->close();

            return response()->json([
                'success' => true,
                'enabled' => false,
                'message' => 'Período "' . $activePeriod->name . '" cerrado. Las evaluaciones han sido deshabilitadas.',
                'period' => null
            ]);
        } else {
            // HABILITAR: Crear y activar nuevo período
            $newPeriod = EvaluationPeriod::create([
                'name' => 'Evaluación ' . now()->format('d M Y'),
                'description' => 'Período creado automáticamente el ' . now()->format('d/m/Y \a \l\a\s H:i'),
                'started_at' => now(),
                'ended_at' => null,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'enabled' => true,
                'message' => 'Nuevo período "' . $newPeriod->name . '" creado. Los jugadores pueden evaluar ahora.',
                'period' => [
                    'id' => $newPeriod->id,
                    'name' => $newPeriod->name,
                    'started_at' => $newPeriod->started_at->format('d/m/Y H:i')
                ]
            ]);
        }
    }

    /**
     * Crear nuevo período de evaluación (entrenadores/analistas)
     */
    public function createPeriod(Request $request)
    {
        $currentUser = auth()->user();

        if (!in_array($currentUser->role, ['entrenador', 'analista'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        // Crear nuevo período
        $period = EvaluationPeriod::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'started_at' => now(),
            'ended_at' => null,
            'is_active' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Período creado exitosamente.',
            'period' => $period
        ]);
    }

    /**
     * Activar un período específico (entrenadores/analistas)
     */
    public function activatePeriod(Request $request, $periodId)
    {
        $currentUser = auth()->user();

        if (!in_array($currentUser->role, ['entrenador', 'analista'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        $period = EvaluationPeriod::findOrFail($periodId);
        $period->activate();

        return response()->json([
            'success' => true,
            'message' => 'Período activado correctamente. Los jugadores pueden evaluar ahora.',
            'period' => $period->fresh()
        ]);
    }

    /**
     * Cerrar período activo (entrenadores/analistas)
     */
    public function closePeriod(Request $request, $periodId)
    {
        $currentUser = auth()->user();

        if (!in_array($currentUser->role, ['entrenador', 'analista'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        $period = EvaluationPeriod::findOrFail($periodId);
        $period->close();

        return response()->json([
            'success' => true,
            'message' => 'Período cerrado correctamente.',
            'period' => $period->fresh()
        ]);
    }

    /**
     * Listar todos los períodos (entrenadores/analistas)
     */
    public function listPeriods()
    {
        $currentUser = auth()->user();

        if (!in_array($currentUser->role, ['entrenador', 'analista'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        $periods = EvaluationPeriod::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'periods' => $periods
        ]);
    }
}
