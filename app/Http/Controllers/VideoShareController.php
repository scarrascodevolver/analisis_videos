<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoOrgShare;
use App\Notifications\VideoShared;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class VideoShareController extends Controller
{
    /**
     * Compartir un video de asociación con un club registrado al torneo.
     * POST /videos/{video}/share
     */
    public function store(Request $request, Video $video)
    {
        $request->validate([
            'target_organization_id' => 'required|exists:organizations,id',
            'division_id'            => 'nullable|exists:tournament_divisions,id',
            'notes'                  => 'nullable|string|max:500',
        ]);

        $sourceOrg = auth()->user()->currentOrganization();

        if (! $sourceOrg || ! $sourceOrg->isAsociacion()) {
            return response()->json(['error' => 'Solo las asociaciones pueden compartir videos.'], 403);
        }

        // Verificar que el video pertenece a la org actual
        if ($video->organization_id !== $sourceOrg->id) {
            return response()->json(['error' => 'No tenés permiso para compartir este video.'], 403);
        }

        $targetOrg = Organization::find($request->target_organization_id);

        if (! $targetOrg || ! $targetOrg->isClub()) {
            return response()->json(['error' => 'El destino debe ser un club.'], 422);
        }

        // Verificar que el club está registrado al torneo del video (cualquier división)
        if ($video->tournament_id) {
            $isRegistered = TournamentRegistration::where('tournament_id', $video->tournament_id)
                ->where('club_organization_id', $targetOrg->id)
                ->where('status', 'active')
                ->exists();

            if (! $isRegistered) {
                return response()->json([
                    'error' => "El club '{$targetOrg->name}' no está inscripto en este torneo.",
                ], 422);
            }

            // Si se especificó división, verificar que pertenece al torneo
            if ($request->division_id) {
                $divisionExists = TournamentDivision::where('id', $request->division_id)
                    ->where('tournament_id', $video->tournament_id)
                    ->exists();
                if (! $divisionExists) {
                    return response()->json(['error' => 'La división no pertenece al torneo de este video.'], 422);
                }
            }
        }

        // Si ya existe un share (activo o revocado), actualizar en lugar de crear
        $share = VideoOrgShare::where('video_id', $video->id)
            ->where('target_organization_id', $targetOrg->id)
            ->first();

        if ($share) {
            if ($share->status === 'active') {
                return response()->json([
                    'error' => "Este video ya fue enviado al club '{$targetOrg->name}'.",
                ], 422);
            }
            // Reactivar share revocado
            $share->update([
                'status'     => 'active',
                'shared_by'  => auth()->id(),
                'division_id'=> $request->division_id,
                'notes'      => $request->notes,
                'shared_at'  => now(),
                'revoked_at' => null,
                'revoked_by' => null,
            ]);
        } else {
            $share = VideoOrgShare::create([
                'video_id'               => $video->id,
                'source_organization_id' => $sourceOrg->id,
                'target_organization_id' => $targetOrg->id,
                'division_id'            => $request->division_id,
                'shared_by'              => auth()->id(),
                'status'                 => 'active',
                'notes'                  => $request->notes,
                'shared_at'              => now(),
            ]);
        }

        // Notify all analysts/coaches of the target club
        User::whereHas('organizations', fn ($q) => $q->where('organizations.id', $targetOrg->id))
            ->whereIn('role', ['analista', 'entrenador'])
            ->get()
            ->each(fn ($u) => $u->notify(new VideoShared($video, $sourceOrg)));

        return response()->json([
            'ok'      => true,
            'message' => "Video enviado a '{$targetOrg->name}' exitosamente.",
            'id'      => $share->id,
        ]);
    }

    /**
     * Lista shares activos de un video (para la asociación).
     * GET /videos/{video}/shares
     */
    public function index(Video $video)
    {
        $sourceOrg = auth()->user()->currentOrganization();

        if (! $sourceOrg || $video->organization_id !== $sourceOrg->id) {
            abort(403);
        }

        $shares = VideoOrgShare::where('video_id', $video->id)
            ->where('status', 'active')
            ->with(['targetOrganization:id,name', 'targetCategory:id,name', 'sharedByUser:id,name'])
            ->orderByDesc('shared_at')
            ->get();

        return view('videos.shares.index', compact('video', 'shares'));
    }

    /**
     * Revocar un share.
     * DELETE /shares/{shareId}
     */
    public function destroy(int $shareId)
    {
        $sourceOrg = auth()->user()->currentOrganization();

        $share = VideoOrgShare::findOrFail($shareId);

        if (! $sourceOrg || $share->source_organization_id !== $sourceOrg->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $share->update([
            'status'     => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
        ]);

        return response()->json(['ok' => true, 'message' => 'El acceso al video fue revocado.']);
    }

    /**
     * Clubes registrados a un torneo (para poplar el modal).
     * GET /api/tournaments/{tournament}/registered-clubs
     */
    public function registeredClubs(Tournament $tournament)
    {
        $sourceOrg = auth()->user()->currentOrganization();

        // Verificar que el torneo pertenece a la org actual
        if ($tournament->organization_id !== $sourceOrg?->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $clubs = TournamentRegistration::where('tournament_id', $tournament->id)
            ->where('status', 'active')
            ->with('clubOrganization:id,name,logo_path')
            ->get()
            ->map(fn ($reg) => [
                'id'       => $reg->clubOrganization->id,
                'name'     => $reg->clubOrganization->name,
                'logo_url' => $reg->clubOrganization->logo_path
                    ? asset('storage/' . $reg->clubOrganization->logo_path)
                    : null,
            ]);

        return response()->json(['clubs' => $clubs]);
    }

    /**
     * Clubes registrados a una división (para poblar el modal de share).
     * GET /api/divisions/{division}/registered-clubs
     */
    public function registeredClubsByDivision(TournamentDivision $division)
    {
        $sourceOrg = auth()->user()->currentOrganization();
        $tournament = Tournament::withoutGlobalScope('organization')->find($division->tournament_id);

        if (! $tournament || $tournament->organization_id !== $sourceOrg?->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $clubs = TournamentRegistration::where('tournament_id', $division->tournament_id)
            ->where('division_id', $division->id)
            ->where('status', 'active')
            ->with('clubOrganization:id,name,logo_path')
            ->get()
            ->map(fn ($reg) => [
                'id'       => $reg->clubOrganization->id,
                'name'     => $reg->clubOrganization->name,
                'logo_url' => $reg->clubOrganization->logo_path
                    ? asset('storage/' . $reg->clubOrganization->logo_path)
                    : null,
            ]);

        return response()->json(['clubs' => $clubs]);
    }

    /**
     * Categorías de una organización (para el modal — cross-org).
     * GET /api/organizations/{org}/categories
     */
    public function organizationCategories(Organization $org)
    {
        $categories = Category::withoutGlobalScope('organization')
            ->where('organization_id', $org->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['categories' => $categories]);
    }
}
