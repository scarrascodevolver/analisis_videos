<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentDivision;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Notifications\TournamentJoinRequest;
use Illuminate\Http\Request;

class TournamentRegistrationController extends Controller
{
    /**
     * Vista para que los clubes exploren y se registren a torneos públicos.
     * GET /tournaments/explore
     */
    public function explore()
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isClub()) {
            abort(403, 'Solo los clubes pueden explorar torneos.');
        }

        // Registrations del club: keyed by tournament_id => registration (with division_id)
        $myRegistrations = TournamentRegistration::where('club_organization_id', $org->id)
            ->whereIn('status', ['active', 'pending'])
            ->get(['tournament_id', 'division_id', 'status'])
            ->keyBy('tournament_id');

        // Torneos públicos de cualquier asociación (cross-org)
        $tournaments = Tournament::withoutGlobalScope('organization')
            ->where('is_public', true)
            ->with(['organization:id,name,logo_path,type', 'divisions'])
            ->withCount('videos')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($tournament) use ($myRegistrations) {
                $myReg = $myRegistrations[$tournament->id] ?? null;
                $tournament->registration_status = $myReg?->status ?? null;
                $tournament->registered_division_id = $myReg?->division_id ?? null;

                return $tournament;
            });

        return view('tournaments.explore', compact('tournaments', 'org'));
    }

    /**
     * API: torneos públicos con estado de registro del club actual.
     * GET /api/tournaments/public
     */
    public function publicIndex()
    {
        $org = auth()->user()->currentOrganization();

        if (! $org) {
            return response()->json(['error' => 'Sin organización activa'], 403);
        }

        $myRegistrations = TournamentRegistration::where('club_organization_id', $org->id)
            ->whereIn('status', ['active', 'pending'])
            ->pluck('status', 'tournament_id');

        $tournaments = Tournament::withoutGlobalScope('organization')
            ->where('is_public', true)
            ->with('organization:id,name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'season' => $t->season,
                'organization' => $t->organization ? $t->organization->name : null,
                'registration_status' => $myRegistrations[$t->id] ?? null,
            ]);

        return response()->json(['tournaments' => $tournaments]);
    }

    /**
     * Registrar club a un torneo (crea solicitud pendiente).
     * POST /tournament-registrations
     */
    public function store(Request $request)
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id',
            'division_id'   => 'nullable|exists:tournament_divisions,id',
        ]);

        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isClub()) {
            return response()->json(['error' => 'Solo clubes pueden registrarse a torneos.'], 403);
        }

        // Verificar que el torneo es público (cross-org query)
        $tournament = Tournament::withoutGlobalScope('organization')->findOrFail($request->tournament_id);

        if (! $tournament->is_public) {
            return response()->json(['error' => 'Este torneo no está disponible públicamente.'], 403);
        }

        // If division_id provided, verify it belongs to this tournament
        $divisionId = null;
        if ($request->filled('division_id')) {
            $division = TournamentDivision::where('id', $request->division_id)
                ->where('tournament_id', $tournament->id)
                ->first();

            if (! $division) {
                return response()->json(['error' => 'La división no pertenece a este torneo.'], 422);
            }

            $divisionId = $division->id;
        }

        // Check existing registration
        $existing = TournamentRegistration::where('tournament_id', $tournament->id)
            ->where('club_organization_id', $org->id)
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Ya tenés una solicitud pendiente para este torneo.',
                ], 422);
            }

            if ($existing->status === 'active') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Ya estás inscripto en este torneo.',
                ], 422);
            }

            // Status is 'withdrawn' or 'rejected' — allow re-request
            $existing->update([
                'division_id'   => $divisionId,
                'status'        => 'pending',
                'registered_at' => now(),
                'withdrawn_at'  => null,
                'rejected_at'   => null,
            ]);
            $registration = $existing;
        } else {
            $registration = TournamentRegistration::create([
                'tournament_id'       => $tournament->id,
                'division_id'         => $divisionId,
                'club_organization_id' => $org->id,
                'status'              => 'pending',
                'registered_at'       => now(),
            ]);
        }

        // Notify all analysts/coaches of the association that owns this tournament
        $adminUsers = User::whereHas('organizations', function ($q) use ($tournament) {
            $q->where('organizations.id', $tournament->organization_id);
        })->whereIn('role', ['analista', 'entrenador'])->get();

        foreach ($adminUsers as $adminUser) {
            $adminUser->notify(new TournamentJoinRequest($registration, $tournament, $org));
        }

        return response()->json([
            'ok' => true,
            'message' => 'Solicitud enviada. Esperá la aprobación de la asociación.',
            'id' => $registration->id,
        ]);
    }

    /**
     * Aprobar solicitud de inscripción (solo la asociación dueña del torneo).
     * POST /tournament-registrations/{registrationId}/approve
     */
    public function approve(int $registrationId)
    {
        $org = auth()->user()->currentOrganization();
        $registration = TournamentRegistration::findOrFail($registrationId);
        $tournament = Tournament::withoutGlobalScope('organization')->find($registration->tournament_id);

        // Only the association that owns the tournament can approve
        if (! $org || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $registration->update(['status' => 'active']);

        // Marcar la notificación relacionada como leída
        auth()->user()->unreadNotifications()
            ->where('type', \App\Notifications\TournamentJoinRequest::class)
            ->get()
            ->filter(fn ($n) => ($n->data['registration_id'] ?? null) == $registrationId)
            ->each(fn ($n) => $n->markAsRead());

        return response()->json([
            'ok' => true,
            'message' => "Solicitud de {$registration->clubOrganization->name} aprobada.",
        ]);
    }

    /**
     * Rechazar solicitud de inscripción (solo la asociación dueña del torneo).
     * POST /tournament-registrations/{registrationId}/reject
     */
    public function reject(int $registrationId)
    {
        $org = auth()->user()->currentOrganization();
        $registration = TournamentRegistration::findOrFail($registrationId);
        $tournament = Tournament::withoutGlobalScope('organization')->find($registration->tournament_id);

        if (! $org || $tournament->organization_id !== $org->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $registration->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        // Marcar la notificación relacionada como leída
        auth()->user()->unreadNotifications()
            ->where('type', \App\Notifications\TournamentJoinRequest::class)
            ->get()
            ->filter(fn ($n) => ($n->data['registration_id'] ?? null) == $registrationId)
            ->each(fn ($n) => $n->markAsRead());

        return response()->json([
            'ok' => true,
            'message' => "Solicitud de {$registration->clubOrganization->name} rechazada.",
        ]);
    }

    /**
     * Darse de baja de un torneo (lookup por tournament_id + org del club actual).
     * DELETE /tournament-registrations/by-tournament/{tournamentId}
     */
    public function destroy(int $tournamentId)
    {
        $org = auth()->user()->currentOrganization();

        if (! $org || ! $org->isClub()) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $registration = TournamentRegistration::where('tournament_id', $tournamentId)
            ->where('club_organization_id', $org->id)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if (! $registration) {
            return response()->json(['error' => 'No estás inscripto en este torneo.'], 404);
        }

        $registration->update([
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        return response()->json(['ok' => true, 'message' => 'Te diste de baja del torneo.']);
    }
}
