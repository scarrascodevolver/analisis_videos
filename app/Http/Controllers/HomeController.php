<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Category;
use App\Models\Video;
use App\Models\VideoAssignment;
use App\Models\RugbySituation;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard based on user role.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Datos comunes para todos los dashboards
        $data = [
            'user' => $user,
            'teams' => Team::all(),
            'categories' => Category::all(),
            'rugbySituations' => RugbySituation::active()->ordered()->get()->groupBy('category'),
            'rugbyStats' => $this->getRugbyStats(),
        ];

        // Dashboard específico según el rol
        switch ($user->role) {
            case 'analista':
                return $this->analystDashboard($data);
            case 'entrenador':
                return $this->coachDashboard($data);
            case 'jugador':
                return $this->playerDashboard($data);
            default:
                return $this->generalDashboard($data);
        }
    }

    private function analystDashboard($data)
    {
        $data['uploadedVideos'] = Video::where('uploaded_by', auth()->id())
                                      ->with(['analyzedTeam', 'category'])
                                      ->latest()
                                      ->take(10)
                                      ->get();
        
        // $data['pendingAssignments'] = VideoAssignment::where('assigned_by', auth()->id())
        //                                             ->where('status', 'assigned')
        //                                             ->with(['video', 'assignedTo'])
        //                                             ->count();
        $data['pendingAssignments'] = 0;

        $data['totalVideos'] = Video::where('uploaded_by', auth()->id())->count();
        
        return view('dashboards.analyst', $data);
    }

    private function playerDashboard($data)
    {
        // $data['assignedVideos'] = VideoAssignment::where('assigned_to', auth()->id())
        //                                         ->with(['video.analyzedTeam', 'video.category', 'assignedBy'])
        //                                         ->latest()
        //                                         ->take(10)
        //                                         ->get();
        $data['assignedVideos'] = collect([]);
        
        // $data['completedAssignments'] = VideoAssignment::where('assigned_to', auth()->id())
        //                                               ->where('status', 'completed')
        //                                               ->count();
        $data['completedAssignments'] = 0;

        // $data['pendingAssignments'] = VideoAssignment::where('assigned_to', auth()->id())
        //                                             ->where('status', 'assigned')
        //                                             ->count();
        $data['pendingAssignments'] = 0;
        
        return view('dashboards.player', $data);
    }

    private function coachDashboard($data)
    {
        $data['recentVideos'] = Video::with(['analyzedTeam', 'category', 'uploader'])
                                    ->latest()
                                    ->take(10)
                                    ->get();
        
        $data['totalVideos'] = Video::count();
        $data['totalUsers'] = \App\Models\User::count();
        // $data['pendingAssignments'] = VideoAssignment::where('status', 'assigned')->count();
        $data['pendingAssignments'] = 0;
        
        return view('dashboards.coach', $data);
    }

    private function generalDashboard($data)
    {
        return view('dashboards.general', $data);
    }

    private function getRugbyStats()
    {
        $stats = [];
        
        // Stats by rugby situation category
        $rugbySituations = RugbySituation::active()->get();
        
        foreach ($rugbySituations->groupBy('category') as $category => $situations) {
            $videoCount = Video::whereIn('rugby_situation_id', $situations->pluck('id'))->count();
            $stats['categories'][$category] = $videoCount;
        }
        
        // Most used rugby situations
        $stats['topSituations'] = Video::select('rugby_situation_id')
                                      ->whereNotNull('rugby_situation_id')
                                      ->with('rugbySituation')
                                      ->selectRaw('rugby_situation_id, count(*) as video_count')
                                      ->groupBy('rugby_situation_id')
                                      ->orderBy('video_count', 'desc')
                                      ->take(5)
                                      ->get();
        
        // Total videos with rugby situations
        $stats['totalWithSituations'] = Video::whereNotNull('rugby_situation_id')->count();
        $stats['totalVideos'] = Video::count();
        
        return $stats;
    }
}
