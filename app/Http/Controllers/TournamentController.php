<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\VideoOrgShare;
use App\Notifications\TournamentJoinRequest;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    /**
     * Tournament detail page — divisions, enrolled clubs, pending requests.
     * GET /tournaments/{tournament}
     */
    public function show(Tournament $tournament)
    {
        $org = auth()->user()->currentOrganization();

        if (! $org) {
            abort(403);
        }

        // Clubs should use the explore page
        if (! $org->isAsociacion()) {
            return redirect()->route('tournaments.explore');
        }

        // Only the owning association can see this tournament
        if ($tournament->organization_id !== $org->id) {
            abort(403);
        }

        // Eager-load divisions with their registrations
        $tournament->load([
            'divisions' => fn($q) => $q->orderBy('order')->orderBy('name'),
            'registrations' => fn($q) => $q->whereIn('status', ['active', 'pending'])
                ->with('clubOrganization:id,name,logo_path'),
        ]);

        // Mark tournament_join_request notifications for this tournament as read
        auth()->user()->unreadNotifications()
            ->where('type', TournamentJoinRequest::class)
            ->get()
            ->filter(fn($n) => ($n->data['tournament_id'] ?? null) == $tournament->id)
            ->each(fn($n) => $n->markAsRead());

        $suggestions = ['M8','M10','M12','M14','M16','M18','M20','Adulta','Femenino','Seven'];
        $existingNames = $tournament->divisions->pluck('name')->map(fn($n) => strtolower($n))->toArray();
        $remainingSuggestions = array_values(array_filter($suggestions, fn($s) => !in_array(strtolower($s), $existingNames)));

        return view('tournaments.show', compact('tournament', 'remainingSuggestions'));
    }

    /**
     * Show the tournament management index.
     * GET /tournaments
     */
    public function index()
    {
        $org = auth()->user()->currentOrganization();

        if (! $org) {
            abort(403);
        }

        // Clubs should use the explore page
        if (! $org->isAsociacion()) {
            return redirect()->route('tournaments.explore');
        }

        $tournaments = Tournament::withCount('videos')
            ->with([
                'divisions',
                'registrations' => fn ($q) => $q->whereIn('status', ['active', 'pending']),
            ])
            ->orderBy('name')
            ->get();

        $pendingRegistrations = collect();
        if ($org && $org->isAsociacion()) {
            // Load all pending registrations for tournaments of this association
            $tournamentIds = $tournaments->pluck('id');
            $pendingRegistrations = TournamentRegistration::whereIn('tournament_id', $tournamentIds)
                ->where('status', 'pending')
                ->with(['clubOrganization:id,name,logo_path', 'tournament:id,name'])
                ->orderBy('registered_at')
                ->get();

            // Marcar notificaciones de solicitudes de torneos como leídas al visitar la página
            auth()->user()->unreadNotifications()
                ->where('type', \App\Notifications\TournamentJoinRequest::class)
                ->get()
                ->markAsRead();
        }

        return view('tournaments.index', compact('tournaments', 'pendingRegistrations'));
    }

    /**
     * Update a tournament's name and season inline.
     * PUT /tournaments/{tournament}
     */
    public function rename(Request $request, Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isAsociacion() || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $request->validate(['name' => 'required|string|max:255']);
        $tournament->update(['name' => $request->name]);

        return response()->json(['ok' => true, 'name' => $tournament->name]);
    }

    public function update(Request $request, Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isAsociacion() || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

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
        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isAsociacion() || $tournament->organization_id !== $org->id) {
            abort(403);
        }

        if ($tournament->videos()->exists()) {
            return back()->with('error', 'No se puede eliminar: el torneo tiene videos asociados.');
        }

        $tournament->delete();

        return back()->with('success', 'Torneo eliminado correctamente.');
    }

    /**
     * Delete a tournament via AJAX from the folder context menu.
     * All associated videos are permanently deleted from Bunny Stream
     * before the tournament row is removed.
     * DELETE /api/tournaments/{tournament}
     */
    public function apiDestroy(Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isAsociacion() || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        set_time_limit(120);

        $videos = $tournament->videos()->with('organization')->get();
        $deletedCount = 0;

        foreach ($videos as $video) {
            // Bunny Stream
            if ($video->bunny_video_id) {
                try {
                    \App\Services\BunnyStreamService::forOrganization($video->organization)
                        ->deleteVideo($video->bunny_video_id);
                } catch (\Exception $e) {
                    \Log::warning("Tournament delete — Bunny delete failed for video {$video->id}: ".$e->getMessage());
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
     * Alternar visibilidad pública de un torneo (solo asociaciones).
     * PATCH /api/tournaments/{tournament}/toggle-public
     */
    public function togglePublic(Tournament $tournament): \Illuminate\Http\JsonResponse
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        if (! $org->isAsociacion()) {
            return response()->json(['error' => 'Solo las asociaciones pueden publicar torneos.'], 403);
        }

        $becomingPrivate = $tournament->is_public; // antes de actualizar
        $tournament->update(['is_public' => ! $tournament->is_public]);

        // Al privatizar: revocar todos los VideoOrgShare de videos de este torneo
        if ($becomingPrivate) {
            $videoIds = $tournament->videos()->withoutGlobalScopes()->pluck('id');
            if ($videoIds->isNotEmpty()) {
                VideoOrgShare::whereIn('video_id', $videoIds)
                    ->where('status', 'active')
                    ->update([
                        'status'     => 'revoked',
                        'revoked_at' => now(),
                        'revoked_by' => auth()->id(),
                    ]);
            }
        }

        return response()->json([
            'ok' => true,
            'is_public' => $tournament->is_public,
            'message' => $tournament->is_public
                ? 'Torneo publicado. Los clubes pueden inscribirse.'
                : 'Torneo ocultado. Los clubes no pueden inscribirse ni ver sus videos.',
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
        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isAsociacion()) {
            return response()->json(['error' => 'Solo las asociaciones pueden crear torneos.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'nullable|string|max:20',
        ]);

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
