<?php

namespace App\Http\Controllers;

use App\Models\VideoAssignment;
use App\Models\Video;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VideoAssigned;

class VideoAssignmentController extends Controller
{
    public function index()
    {
        $assignments = VideoAssignment::with(['video.analyzedTeam', 'video.rivalTeam', 'video.category', 'assignedTo', 'assignedBy'])
                                    ->latest()
                                    ->paginate(15);

        return view('assignments.index', compact('assignments'));
    }

    public function create()
    {
        $videos = Video::with(['analyzedTeam', 'rivalTeam', 'category'])
                      ->latest()
                      ->get();

        $players = User::where(function($query) {
                $query->where('role', 'jugador')
                      ->orWhereHas('profile', function($q) {
                          $q->where('can_receive_assignments', true);
                      });
            })
            ->with('profile')
            ->orderBy('name')
            ->get();

        return view('assignments.create', compact('videos', 'players'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'video_id' => 'required|exists:videos,id',
            'player_id' => 'required|exists:users,id',
            'priority' => 'required|in:baja,media,alta,critica',
            'due_date' => 'required|date|after:today',
            'instructions' => 'nullable|string',
            'focus_areas' => 'nullable|array',
            'focus_areas.*' => 'in:tecnico,tactico,fisico,mental,liderazgo'
        ]);

        // Check if assignment already exists
        $existingAssignment = VideoAssignment::where('video_id', $request->video_id)
                                           ->where('assigned_to', $request->player_id)
                                           ->first();

        if ($existingAssignment) {
            return back()->withErrors(['video_id' => 'Este video ya está asignado a este jugador']);
        }

        $assignment = VideoAssignment::create([
            'video_id' => $request->video_id,
            'player_id' => $request->player_id,
            'analyst_id' => auth()->id(),
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'instructions' => $request->instructions,
            'focus_areas' => $request->focus_areas ? json_encode($request->focus_areas) : null,
            'status' => 'asignado',
            'assigned_at' => now()
        ]);

        // Send notification to player
        $player = User::find($request->player_id);
        Notification::send($player, new VideoAssigned($assignment));

        return redirect()->route('analyst.assignments.index')
                         ->with('success', 'Video asignado exitosamente al jugador ' . $player->name);
    }

    public function show(VideoAssignment $assignment)
    {
        $assignment->load([
            'video.analyzedTeam', 
            'video.rivalTeam', 
            'video.category',
            'video.comments.user',
            'player', 
            'analyst'
        ]);

        return view('assignments.show', compact('assignment'));
    }

    public function edit(VideoAssignment $assignment)
    {
        $videos = Video::with(['analyzedTeam', 'rivalTeam', 'category'])->get();
        $players = User::where(function($query) {
                $query->where('role', 'jugador')
                      ->orWhereHas('profile', function($q) {
                          $q->where('can_receive_assignments', true);
                      });
            })
            ->with('profile')
            ->orderBy('name')
            ->get();

        return view('assignments.edit', compact('assignment', 'videos', 'players'));
    }

    public function update(Request $request, VideoAssignment $assignment)
    {
        $request->validate([
            'priority' => 'required|in:baja,media,alta,critica',
            'due_date' => 'required|date',
            'instructions' => 'nullable|string',
            'focus_areas' => 'nullable|array',
            'focus_areas.*' => 'in:tecnico,tactico,fisico,mental,liderazgo',
            'status' => 'required|in:asignado,en_progreso,completado,cancelado'
        ]);

        $assignment->update([
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'instructions' => $request->instructions,
            'focus_areas' => $request->focus_areas ? json_encode($request->focus_areas) : null,
            'status' => $request->status
        ]);

        return redirect()->route('analyst.assignments.show', $assignment)
                         ->with('success', 'Asignación actualizada exitosamente');
    }

    public function destroy(VideoAssignment $assignment)
    {
        $assignment->delete();

        return redirect()->route('analyst.assignments.index')
                         ->with('success', 'Asignación eliminada exitosamente');
    }

    public function markCompleted(VideoAssignment $assignment)
    {
        $assignment->update([
            'status' => 'completado',
            'completed_at' => now()
        ]);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Asignación marcada como completada'
            ]);
        }

        return back()->with('success', 'Asignación marcada como completada');
    }

    public function playerAccept(VideoAssignment $assignment)
    {
        if ($assignment->player_id !== auth()->id()) {
            abort(403, 'No autorizado');
        }

        $assignment->update([
            'status' => 'en_progreso',
            'accepted_at' => now()
        ]);

        return redirect()->route('player.videos')
                         ->with('success', 'Asignación aceptada. Puedes comenzar el análisis.');
    }

    public function playerSubmit(Request $request, VideoAssignment $assignment)
    {
        if ($assignment->player_id !== auth()->id()) {
            abort(403, 'No autorizado');
        }

        $request->validate([
            'player_notes' => 'required|string|min:50',
            'self_evaluation' => 'required|integer|min:1|max:10',
            'areas_identified' => 'required|array|min:1',
            'areas_identified.*' => 'in:tecnico,tactico,fisico,mental,liderazgo'
        ]);

        $assignment->update([
            'status' => 'completado',
            'completed_at' => now(),
            'player_notes' => $request->player_notes,
            'self_evaluation' => $request->self_evaluation,
            'areas_identified' => json_encode($request->areas_identified)
        ]);

        // Notify analyst of completion
        Notification::send($assignment->analyst, new \App\Notifications\AssignmentCompleted($assignment));

        return redirect()->route('player.videos')
                         ->with('success', 'Análisis completado y enviado al analista');
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:assign,complete,cancel',
            'assignments' => 'required|array',
            'assignments.*' => 'exists:video_assignments,id'
        ]);

        $assignments = VideoAssignment::whereIn('id', $request->assignments)->get();

        foreach ($assignments as $assignment) {
            switch ($request->action) {
                case 'complete':
                    $assignment->update(['status' => 'completado', 'completed_at' => now()]);
                    break;
                case 'cancel':
                    $assignment->update(['status' => 'cancelado']);
                    break;
            }
        }

        return back()->with('success', 'Acción aplicada a ' . count($assignments) . ' asignaciones');
    }
}