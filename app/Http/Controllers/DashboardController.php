<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAssignment;
use App\Models\Team;
use App\Models\Category;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function analyst()
    {
        return view('dashboards.analyst');
    }

    public function reports()
    {
        $totalVideos = Video::count();
        // $totalAssignments = VideoAssignment::count();
        // $completedAssignments = VideoAssignment::where('status', 'completed')->count();
        // $pendingAssignments = VideoAssignment::where('status', 'assigned')->count();
        $totalAssignments = 0;
        $completedAssignments = 0;
        $pendingAssignments = 0;
        
        return view('reports.analyst', compact(
            'totalVideos', 'totalAssignments', 'completedAssignments', 'pendingAssignments'
        ));
    }

    public function playerVideos()
    {
        $assignments = VideoAssignment::where('assigned_to', auth()->id())
                                    ->with(['video.analyzedTeam', 'video.rivalTeam', 'video.category'])
                                    ->latest()
                                    ->get();

        return view('dashboards.player-videos', compact('assignments'));
    }

    public function playerCompleted()
    {
        $assignments = VideoAssignment::where('assigned_to', auth()->id())
                                    ->with(['video.analyzedTeam', 'video.rivalTeam', 'video.category'])
                                    ->latest()
                                    ->get();

        return view('dashboards.player-completed', compact('assignments'));
    }

    public function playerPending()
    {
        $assignments = VideoAssignment::where('assigned_to', auth()->id())
                                    ->with(['video.analyzedTeam', 'video.rivalTeam', 'video.category'])
                                    ->latest()
                                    ->get();

        return view('dashboards.player-pending', compact('assignments'));
    }

    public function coachVideos()
    {
        $videos = Video::with(['analyzedTeam', 'rivalTeam', 'category', 'uploader'])
                      ->coachVisible(auth()->user())
                      ->latest()
                      ->paginate(12);

        return view('dashboards.coach-videos', compact('videos'));
    }

    public function coachUsers()
    {
        $user = auth()->user();

        // Si es analista o staff, ve todos los usuarios
        if (in_array($user->role, ['analista', 'staff', 'director_tecnico', 'director_club'])) {
            $users = User::with('profile')->get();
        } else {
            // Si es entrenador, solo ve jugadores de su categoría
            $coachCategoryId = $user->profile?->user_category_id;

            $users = User::where('role', 'jugador')
                         ->when($coachCategoryId, function($query) use ($coachCategoryId) {
                             return $query->whereHas('profile', function($q) use ($coachCategoryId) {
                                 $q->where('user_category_id', $coachCategoryId);
                             });
                         })
                         ->with('profile')
                         ->get();
        }

        return view('dashboards.coach-users', compact('users'));
    }

    public function coachAssignments()
    {
        $user = auth()->user();

        $assignments = VideoAssignment::with(['video', 'assignedTo', 'assignedBy'])
                                     ->when($user->role === 'entrenador', function($query) use ($user) {
                                         $coachCategoryId = $user->profile?->user_category_id;
                                         if ($coachCategoryId) {
                                             // Solo asignaciones de videos de su categoría
                                             return $query->whereHas('video', function($q) use ($coachCategoryId) {
                                                 $q->where('category_id', $coachCategoryId);
                                             });
                                         }
                                     })
                                     ->latest()
                                     ->paginate(15);

        return view('dashboards.coach-assignments', compact('assignments'));
    }

    public function coachRivals()
    {
        $teams = Team::where('is_own_team', false)->get();
        
        return view('dashboards.coach-rivals', compact('teams'));
    }

    public function teamReport()
    {
        // Team performance metrics
        return view('reports.team');
    }

    public function playersCompare()
    {
        $players = User::where('role', 'jugador')->with('profile')->get();
        
        return view('dashboards.players-compare', compact('players'));
    }

    public function trainingPlan()
    {
        return view('dashboards.training-plan');
    }

    public function roster()
    {
        $players = User::where('role', 'jugador')->with('profile')->get();
        
        return view('dashboards.roster', compact('players'));
    }

    public function playerProfile(User $user)
    {
        $user->load('profile');
        
        return view('dashboards.player-profile', compact('user'));
    }

    public function playerAssign(User $user)
    {
        $videos = Video::with(['analyzedTeam', 'rivalTeam', 'category'])
                      ->coachVisible(auth()->user())
                      ->latest()
                      ->get();

        return view('dashboards.player-assign', compact('user', 'videos'));
    }
}
