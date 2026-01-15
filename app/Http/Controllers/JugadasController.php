<?php

namespace App\Http\Controllers;

use App\Models\Jugada;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JugadasController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the plays editor.
     */
    public function index()
    {
        return view('jugadas.index');
    }

    /**
     * API: Listar jugadas de la organización
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $user = auth()->user();
        $currentOrg = $user ? $user->currentOrganization() : null;

        // Debug info
        \Log::info('Jugadas API Index', [
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : null,
            'current_org_id' => $currentOrg ? $currentOrg->id : null,
            'current_org_name' => $currentOrg ? $currentOrg->name : null,
        ]);

        $jugadas = Jugada::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($jugada) {
                return [
                    'id' => $jugada->id,
                    'name' => $jugada->name,
                    'category' => $jugada->category,
                    'categoryIcon' => $jugada->category_icon,
                    'thumbnail' => $jugada->thumbnail,
                    'user' => $jugada->user->name ?? 'Desconocido',
                    'created_at' => $jugada->created_at->format('d/m/Y H:i'),
                    'data' => $jugada->data,
                ];
            });

        \Log::info('Jugadas encontradas: ' . $jugadas->count());

        return response()->json([
            'success' => true,
            'jugadas' => $jugadas,
            'debug' => [
                'org_id' => $currentOrg ? $currentOrg->id : null,
                'org_name' => $currentOrg ? $currentOrg->name : null,
                'count' => $jugadas->count(),
            ],
        ]);
    }

    /**
     * API: Guardar nueva jugada
     */
    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:forwards,backs,full_team',
            'data' => 'required|array',
            'thumbnail' => 'nullable|string',
        ]);

        $user = auth()->user();
        $organization = $user->currentOrganization();

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una organización asignada.',
            ], 403);
        }

        $jugada = Jugada::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'data' => $validated['data'],
            'thumbnail' => $validated['thumbnail'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jugada guardada exitosamente.',
            'jugada' => [
                'id' => $jugada->id,
                'name' => $jugada->name,
                'category' => $jugada->category,
                'categoryIcon' => $jugada->category_icon,
                'thumbnail' => $jugada->thumbnail,
                'user' => $user->name,
                'created_at' => $jugada->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * API: Obtener una jugada específica
     */
    public function apiShow(Jugada $jugada): JsonResponse
    {
        return response()->json([
            'success' => true,
            'jugada' => [
                'id' => $jugada->id,
                'name' => $jugada->name,
                'category' => $jugada->category,
                'categoryIcon' => $jugada->category_icon,
                'thumbnail' => $jugada->thumbnail,
                'user' => $jugada->user->name ?? 'Desconocido',
                'created_at' => $jugada->created_at->format('d/m/Y H:i'),
                'data' => $jugada->data,
            ],
        ]);
    }

    /**
     * API: Eliminar jugada
     */
    public function apiDestroy(Jugada $jugada): JsonResponse
    {
        $name = $jugada->name;
        $jugada->delete();

        return response()->json([
            'success' => true,
            'message' => "Jugada '{$name}' eliminada.",
        ]);
    }
}
