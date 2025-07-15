<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Team;
use App\Models\Category;
use App\Models\VideoComment;
use App\Models\RugbySituation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::with(['analyzedTeam', 'rivalTeam', 'category', 'uploader', 'rugbySituation']);

        // Filter by rugby situation
        if ($request->filled('rugby_situation')) {
            $query->where('rugby_situation_id', $request->rugby_situation);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by team
        if ($request->filled('team')) {
            $query->where(function($q) use ($request) {
                $q->where('analyzed_team_id', $request->team)
                  ->orWhere('rival_team_id', $request->team);
            });
        }

        // Search by title
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $videos = $query->latest()->paginate(12);

        // Get filter data
        $rugbySituations = RugbySituation::active()->ordered()->get()->groupBy('category');
        $categories = Category::all();
        $teams = Team::all();

        return view('videos.index', compact('videos', 'rugbySituations', 'categories', 'teams'));
    }

    public function create()
    {
        $teams = Team::all();
        $categories = Category::all();
        $ownTeam = Team::where('is_own_team', true)->first();
        $rivalTeams = Team::where('is_own_team', false)->get();
        $rugbySituations = RugbySituation::active()->ordered()->get()->groupBy('category');

        return view('videos.create', compact('teams', 'categories', 'ownTeam', 'rivalTeams', 'rugbySituations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_file' => 'required|file|mimes:mp4,mov,avi|max:1048576', // 1GB max
            'analyzed_team_id' => 'required|exists:teams,id',
            'rival_team_id' => 'nullable|exists:teams,id',
            'category_id' => 'required|exists:categories,id',
            'rugby_situation_id' => 'nullable|exists:rugby_situations,id',
            'match_date' => 'required|date',
        ]);

        $file = $request->file('video_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('videos', $filename, 'public');

        $video = Video::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $path,
            'file_name' => $filename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'analyzed_team_id' => $request->analyzed_team_id,
            'rival_team_id' => $request->rival_team_id,
            'category_id' => $request->category_id,
            'rugby_situation_id' => $request->rugby_situation_id,
            'match_date' => $request->match_date,
            'status' => 'pending'
        ]);

        return redirect()->route('videos.show', $video)
                         ->with('success', 'Video subido exitosamente');
    }

    public function show(Video $video)
    {
        $video->load(['analyzedTeam', 'rivalTeam', 'category', 'uploader', 'comments.user', 'comments.replies.user']);
        
        $comments = $video->comments()
                         ->whereNull('parent_id')
                         ->with(['user', 'replies.user'])
                         ->orderBy('timestamp_seconds')
                         ->get();

        return view('videos.show', compact('video', 'comments'));
    }

    public function edit(Video $video)
    {
        $teams = Team::all();
        $categories = Category::all();
        $ownTeam = Team::where('is_own_team', true)->first();
        $rivalTeams = Team::where('is_own_team', false)->get();

        return view('videos.edit', compact('video', 'teams', 'categories', 'ownTeam', 'rivalTeams'));
    }

    public function update(Request $request, Video $video)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'analyzed_team_id' => 'required|exists:teams,id',
            'rival_team_id' => 'nullable|exists:teams,id',
            'category_id' => 'required|exists:categories,id',
            'match_date' => 'required|date',
        ]);

        $video->update($request->only([
            'title', 'description', 'analyzed_team_id', 
            'rival_team_id', 'category_id', 'match_date'
        ]));

        return redirect()->route('videos.show', $video)
                         ->with('success', 'Video actualizado exitosamente');
    }

    public function destroy(Video $video)
    {
        // Delete file from storage
        Storage::disk('public')->delete($video->file_path);
        
        $video->delete();

        return redirect()->route('videos.index')
                         ->with('success', 'Video eliminado exitosamente');
    }

    public function analytics(Video $video)
    {
        $comments = $video->comments()
                         ->with('user')
                         ->orderBy('timestamp_seconds')
                         ->get();

        $commentsByCategory = $comments->groupBy('category');
        $commentsByPriority = $comments->groupBy('priority');
        $commentsByStatus = $comments->groupBy('status');

        return view('videos.analytics', compact(
            'video', 'comments', 'commentsByCategory', 
            'commentsByPriority', 'commentsByStatus'
        ));
    }

    public function playerUpload()
    {
        $teams = Team::all();
        $categories = Category::all();
        $ownTeam = Team::where('is_own_team', true)->first();

        return view('videos.player-upload', compact('teams', 'categories', 'ownTeam'));
    }

    public function playerStore(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'video_file' => 'required|file|mimes:mp4,mov,avi|max:524288', // 512MB max for players
            'category_id' => 'required|exists:categories,id',
            'analysis_request' => 'required|string'
        ]);

        $file = $request->file('video_file');
        $filename = time() . '_player_' . $file->getClientOriginalName();
        $path = $file->storeAs('videos/player-uploads', $filename, 'public');

        $ownTeam = Team::where('is_own_team', true)->first();

        $video = Video::create([
            'title' => $request->title . ' (Solicitud de Análisis)',
            'description' => $request->description . "\n\nSolicitud específica: " . $request->analysis_request,
            'file_path' => $path,
            'file_name' => $filename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
            'analyzed_team_id' => $ownTeam->id,
            'rival_team_id' => null,
            'category_id' => $request->category_id,
            'match_date' => now()->toDateString(),
            'status' => 'pending'
        ]);

        return redirect()->route('player.videos')
                         ->with('success', 'Video subido exitosamente. Un analista lo revisará pronto.');
    }
}
