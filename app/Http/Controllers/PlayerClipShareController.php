<?php

namespace App\Http\Controllers;

use App\Models\SharedClip;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Models\VideoClip;
use Illuminate\Http\Request;

class PlayerClipShareController extends Controller
{
    /**
     * POST api/clips/{clip}/share-with-player
     * Comparte un clip con un jugador (misma org o de otro club via torneo).
     */
    public function store(Request $request, VideoClip $clip)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);

        $sharer = auth()->user();

        if (! in_array($sharer->role, ['analista', 'entrenador'])) {
            return response()->json(['error' => 'Solo analistas y entrenadores pueden compartir clips.'], 403);
        }

        $fromOrg = $sharer->currentOrganization();
        if (! $fromOrg) {
            return response()->json(['error' => 'Sin organización activa.'], 422);
        }

        $player = User::findOrFail($request->user_id);

        // Verificar que el jugador pertenece a una org accesible
        $accessibleOrgIds = $this->accessibleOrgIds($clip, $fromOrg->id);
        $playerOrg = $player->organizations()
            ->whereIn('organizations.id', $accessibleOrgIds)
            ->first();

        if (! $playerOrg) {
            return response()->json(['error' => 'No tenés acceso a ese jugador.'], 403);
        }

        // Evitar duplicados (mismo clip + mismo jugador)
        $existing = SharedClip::where('video_clip_id', $clip->id)
            ->where('shared_with_user_id', $player->id)
            ->first();

        if ($existing) {
            // Actualizar mensaje si cambió y marcar como no leído
            $existing->update([
                'message'  => $request->message,
                'read_at'  => null,
            ]);
            return response()->json(['success' => true, 'updated' => true]);
        }

        SharedClip::create([
            'video_clip_id'        => $clip->id,
            'video_id'             => $clip->video_id,
            'shared_by_user_id'    => $sharer->id,
            'shared_with_user_id'  => $player->id,
            'from_organization_id' => $fromOrg->id,
            'to_organization_id'   => $playerOrg->id,
            'message'              => $request->message,
        ]);

        // Generar share_token si el clip no tiene uno
        if (! $clip->share_token) {
            $clip->update(['share_token' => \Str::uuid()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * GET api/clips/search-players
     * Busca jugadores accesibles para compartir (propia org + clubes del torneo).
     */
    public function searchPlayers(Request $request)
    {
        $q          = trim($request->input('q', ''));
        $videoId    = $request->input('video_id');
        $user       = auth()->user();
        $org        = $user->currentOrganization();

        if (! $org) {
            return response()->json([]);
        }

        // Determinar org IDs accesibles
        $orgIds = collect([$org->id]);

        if ($videoId) {
            $video = \App\Models\Video::withoutGlobalScopes()->find($videoId);
            if ($video && $video->tournament_id) {
                $registeredIds = TournamentRegistration::where('tournament_id', $video->tournament_id)
                    ->where('status', 'active')
                    ->pluck('club_organization_id');
                $orgIds = $orgIds->merge($registeredIds)->unique();
            }
        }

        $query = User::where('role', 'jugador')
            ->whereHas('organizations', fn ($q) => $q->whereIn('organizations.id', $orgIds->toArray()))
            ->with(['organizations' => fn ($q) => $q->whereIn('organizations.id', $orgIds->toArray())]);

        if (strlen($q) >= 2) {
            $query->where(fn ($sq) =>
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%")
            );
        }

        $players = $query->orderBy('name')->limit(25)->get()
            ->map(function ($player) use ($org) {
                $playerOrg = $player->organizations->first();
                return [
                    'id'         => $player->id,
                    'name'       => $player->name,
                    'org_name'   => $playerOrg?->name ?? '—',
                    'org_id'     => $playerOrg?->id,
                    'is_own_org' => ($playerOrg?->id === $org->id),
                ];
            });

        return response()->json($players);
    }

    /**
     * GET my-videos/clips
     * Vista del jugador: clips compartidos conmigo.
     */
    public function myClips()
    {
        $user = auth()->user();

        $sharedClips = SharedClip::where('shared_with_user_id', $user->id)
            ->with([
                'clip.category',
                'video',
                'sharedBy',
                'fromOrganization',
            ])
            ->latest()
            ->paginate(20);

        $unreadCount = SharedClip::where('shared_with_user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return view('my-videos.clips', compact('sharedClips', 'unreadCount'));
    }

    /**
     * POST api/shared-clips/{sharedClip}/read
     */
    public function markRead(SharedClip $sharedClip)
    {
        abort_if($sharedClip->shared_with_user_id !== auth()->id(), 403);
        $sharedClip->markAsRead();
        return response()->json(['success' => true]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function accessibleOrgIds(VideoClip $clip, int $fromOrgId): array
    {
        $ids = [$fromOrgId];

        $video = \App\Models\Video::withoutGlobalScopes()->find($clip->video_id);
        if ($video && $video->tournament_id) {
            $registered = TournamentRegistration::where('tournament_id', $video->tournament_id)
                ->where('status', 'active')
                ->pluck('club_organization_id')
                ->toArray();
            $ids = array_unique(array_merge($ids, $registered));
        }

        return $ids;
    }
}
