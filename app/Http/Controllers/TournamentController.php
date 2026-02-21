<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    /**
     * Show the tournament management index.
     * GET /tournaments
     */
    public function index(): \Illuminate\View\View
    {
        $tournaments = Tournament::withCount('videos')->orderBy('name')->get();

        return view('tournaments.index', compact('tournaments'));
    }

    /**
     * Update a tournament's name and season inline.
     * PUT /tournaments/{tournament}
     */
    public function rename(Request $request, Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);
        $tournament->update(['name' => $request->name]);

        return response()->json(['ok' => true, 'name' => $tournament->name]);
    }

    public function update(Request $request, Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'nullable|string|max:20',
        ]);

        $tournament->update($request->only('name', 'season'));

        return response()->json(['success' => true]);
    }

    /**
     * Delete a tournament via the admin web form (tournaments.destroy).
     * Refuses if the tournament has videos to avoid accidental data loss.
     * DELETE /tournaments/{tournament}
     */
    public function destroy(Tournament $tournament): \Illuminate\Http\RedirectResponse
    {
        if ($tournament->videos()->exists()) {
            return back()->with('error', 'No se puede eliminar: el torneo tiene videos asociados.');
        }

        $tournament->delete();

        return back()->with('success', 'Torneo eliminado correctamente.');
    }

    /**
     * Delete a tournament via AJAX from the folder context menu.
     * All associated videos are permanently deleted from Spaces, local storage,
     * and Bunny Stream before the tournament row is removed.
     * DELETE /api/tournaments/{tournament}
     */
    public function apiDestroy(Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        set_time_limit(120);

        $videos = $tournament->videos()->with('organization')->get();
        $deletedCount = 0;

        foreach ($videos as $video) {
            // Spaces — archivo principal
            try {
                if ($video->file_path && \Storage::disk('spaces')->exists($video->file_path)) {
                    \Storage::disk('spaces')->delete($video->file_path);
                }
            } catch (\Exception $e) {
                \Log::warning("Tournament delete — Spaces delete failed for video {$video->id}: " . $e->getMessage());
            }

            // Almacenamiento local — archivo principal
            try {
                \Storage::disk('public')->delete($video->file_path);
            } catch (\Exception $e) {
                // silencioso
            }

            // Thumbnail
            if ($video->thumbnail_path) {
                try {
                    if (\Storage::disk('spaces')->exists($video->thumbnail_path)) {
                        \Storage::disk('spaces')->delete($video->thumbnail_path);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Tournament delete — Spaces thumbnail delete failed for video {$video->id}: " . $e->getMessage());
                }
                try {
                    \Storage::disk('public')->delete($video->thumbnail_path);
                } catch (\Exception $e) {
                    // silencioso
                }
            }

            // Archivo original (antes de compresión), si difiere del principal
            if ($video->original_file_path && $video->original_file_path !== $video->file_path) {
                try {
                    if (\Storage::disk('spaces')->exists($video->original_file_path)) {
                        \Storage::disk('spaces')->delete($video->original_file_path);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Tournament delete — Spaces original delete failed for video {$video->id}: " . $e->getMessage());
                }
                try {
                    \Storage::disk('public')->delete($video->original_file_path);
                } catch (\Exception $e) {
                    // silencioso
                }
            }

            // Bunny Stream
            if ($video->bunny_video_id) {
                try {
                    \App\Services\BunnyStreamService::forOrganization($video->organization)
                        ->deleteVideo($video->bunny_video_id);
                } catch (\Exception $e) {
                    \Log::warning("Tournament delete — Bunny delete failed for video {$video->id}: " . $e->getMessage());
                }
            }

            // Base de datos (activa el boot method del modelo: cancela jobs pendientes,
            // elimina asignaciones y demás relaciones en cascada)
            $video->delete();
            $deletedCount++;
        }

        $tournament->delete();

        return response()->json([
            'ok' => true,
            'message' => $deletedCount > 0
                ? "Torneo eliminado con {$deletedCount} video(s) borrados del servidor."
                : 'Torneo eliminado.',
            'videos_deleted' => $deletedCount,
        ]);
    }

    /**
     * Autocomplete para Select2 — busca torneos de la organización actual.
     * GET /api/tournaments/autocomplete?q=...
     */
    public function autocomplete(Request $request)
    {
        $q = $request->input('q', '');

        $tournaments = Tournament::where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                ->orWhere('season', 'like', "%{$q}%");
        })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'season']);

        return response()->json([
            'results' => $tournaments->map(fn ($t) => [
                'id' => $t->id,
                'text' => $t->season ? "{$t->name} ({$t->season})" : $t->name,
            ]),
        ]);
    }

    /**
     * Crear torneo desde el formulario si no existe.
     * POST /api/tournaments
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'nullable|string|max:20',
        ]);

        $org = auth()->user()->currentOrganization();

        $tournament = Tournament::firstOrCreate(
            [
                'organization_id' => $org->id,
                'name' => $request->name,
                'season' => $request->season,
            ]
        );

        return response()->json([
            'id' => $tournament->id,
            'text' => $tournament->season
                ? "{$tournament->name} ({$tournament->season})"
                : $tournament->name,
        ]);
    }
}
