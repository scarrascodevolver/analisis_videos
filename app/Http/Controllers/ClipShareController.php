<?php

namespace App\Http\Controllers;

use App\Models\VideoClip;

class ClipShareController extends Controller
{
    public function show($clipId)
    {
        // Bypass org global scope — this route is public
        $clip = VideoClip::withoutGlobalScopes()
            ->with([
                'category',
                'video' => fn ($q) => $q->withoutGlobalScopes()->with('organization'),
            ])
            ->findOrFail($clipId);

        $video = $clip->video;

        abort_if(! $video || ! $video->bunny_hls_url, 404, 'Clip no disponible');

        return view('clips.share', compact('clip', 'video'));
    }
}
