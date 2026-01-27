<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MultiCameraController extends Controller
{
    /**
     * Get all angles for a video group
     */
    public function getGroupAngles(Video $video)
    {
        if (!$video->isPartOfGroup()) {
            return response()->json([
                'success' => false,
                'message' => 'This video is not part of a multi-camera group'
            ], 404);
        }

        $master = $video->getMasterVideo();
        $slaves = $video->getSlaveVideos();

        return response()->json([
            'success' => true,
            'master' => [
                'id' => $master->id,
                'title' => $master->title,
                'camera_angle' => $master->camera_angle ?? 'Master',
                'is_synced' => true,
                'sync_offset' => 0,
            ],
            'angles' => $slaves->map(function ($slave) {
                return [
                    'id' => $slave->id,
                    'title' => $slave->title,
                    'camera_angle' => $slave->camera_angle,
                    'is_synced' => $slave->is_synced,
                    'sync_offset' => $slave->sync_offset,
                    'sync_reference_event' => $slave->sync_reference_event,
                    'file_size' => $slave->file_size,
                    'duration' => $slave->duration,
                ];
            })
        ]);
    }

    /**
     * Search videos to associate as angles
     */
    public function searchVideos(Request $request)
    {
        $query = $request->input('query', '');
        $organizationId = auth()->user()->currentOrganization()->id;
        $excludeGroupId = $request->input('exclude_group_id');

        $videos = Video::where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('analyzed_team_name', 'LIKE', "%{$query}%")
                  ->orWhere('rival_team_name', 'LIKE', "%{$query}%");
            })
            ->when($excludeGroupId, function ($q, $groupId) {
                // Exclude videos already in a group
                $q->where(function ($subQ) use ($groupId) {
                    $subQ->whereNull('video_group_id')
                         ->orWhere('video_group_id', '!=', $groupId);
                });
            })
            ->orderByDesc('match_date')
            ->limit(10)
            ->get(['id', 'title', 'match_date', 'file_size', 'duration', 'thumbnail_path']);

        return response()->json([
            'success' => true,
            'videos' => $videos
        ]);
    }

    /**
     * Associate a video as an angle to a master video
     */
    public function associateAngle(Request $request, Video $masterVideo)
    {
        $request->validate([
            'slave_video_id' => 'required|exists:videos,id',
            'camera_angle' => 'required|string|max:100',
        ]);

        $slaveVideo = Video::findOrFail($request->slave_video_id);

        Log::info("=== ASSOCIATE DEBUG START ===");
        Log::info("Master Video ID: {$masterVideo->id}");
        Log::info("Master is_master: " . var_export($masterVideo->is_master, true));
        Log::info("Master isMaster(): " . var_export($masterVideo->isMaster(), true));
        Log::info("Master video_group_id: {$masterVideo->video_group_id}");
        Log::info("Slave Video ID: {$slaveVideo->id}");
        Log::info("Slave video_group_id: {$slaveVideo->video_group_id}");
        Log::info("Slave isPartOfGroup(): " . var_export($slaveVideo->isPartOfGroup(), true));

        // Verify master video can be a master
        if (!$masterVideo->isMaster() && !$masterVideo->isPartOfGroup()) {
            Log::info("Converting video to master...");
            // Make it a master and create a group
            $groupId = Video::generateGroupId();
            $masterVideo->update([
                'video_group_id' => $groupId,
                'is_master' => true,
                'camera_angle' => $request->input('master_angle', 'Master / Tribuna Central'),
            ]);

            // Reload the model to reflect the changes
            $masterVideo->refresh();
            Log::info("After conversion - is_master: " . var_export($masterVideo->is_master, true));
        } else {
            Log::info("Master is already a master or part of group, skipping conversion");
        }

        // Verify slave is not already in a group
        if ($slaveVideo->isPartOfGroup()) {
            Log::warning("Slave video {$slaveVideo->id} is already in group {$slaveVideo->video_group_id}");
            return response()->json([
                'success' => false,
                'message' => 'This video is already part of another group. Remove it first.'
            ], 400);
        }

        // Associate slave to master
        Log::info("Calling associateToMaster with camera_angle: {$request->camera_angle}");
        $success = $slaveVideo->associateToMaster($masterVideo, $request->camera_angle);
        Log::info("associateToMaster returned: " . var_export($success, true));
        Log::info("=== ASSOCIATE DEBUG END ===");

        if ($success) {
            Log::info("Video {$slaveVideo->id} associated to master {$masterVideo->id} as angle: {$request->camera_angle}");

            return response()->json([
                'success' => true,
                'message' => "Angle '{$request->camera_angle}' associated successfully",
                'slave_video' => [
                    'id' => $slaveVideo->id,
                    'title' => $slaveVideo->title,
                    'camera_angle' => $slaveVideo->camera_angle,
                    'is_synced' => false,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to associate angle'
        ], 500);
    }

    /**
     * Remove an angle from a group
     */
    public function removeAngle(Video $video)
    {
        if (!$video->isSlave()) {
            return response()->json([
                'success' => false,
                'message' => 'Only slave videos can be removed from a group'
            ], 400);
        }

        $video->removeFromGroup();

        Log::info("Video {$video->id} removed from group");

        return response()->json([
            'success' => true,
            'message' => 'Angle removed from group successfully'
        ]);
    }

    /**
     * Sync a slave video with the master
     */
    public function syncAngle(Request $request, Video $video)
    {
        $request->validate([
            'sync_offset' => 'required|numeric|between:-300,300', // Max ±5 minutes
            'reference_event' => 'nullable|string|max:255',
        ]);

        if (!$video->isSlave()) {
            return response()->json([
                'success' => false,
                'message' => 'Only slave videos can be synced'
            ], 400);
        }

        $success = $video->syncWithMaster(
            $request->sync_offset,
            $request->input('reference_event')
        );

        if ($success) {
            Log::info("Video {$video->id} synced with offset: {$request->sync_offset}s");

            return response()->json([
                'success' => true,
                'message' => 'Video synced successfully',
                'video' => [
                    'id' => $video->id,
                    'camera_angle' => $video->camera_angle,
                    'is_synced' => true,
                    'sync_offset' => $video->sync_offset,
                    'sync_reference_event' => $video->sync_reference_event,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to sync video'
        ], 500);
    }

    /**
     * Get video stream URL for multi-camera player
     */
    public function getStreamUrl(Video $video)
    {
        return response()->json([
            'success' => true,
            'stream_url' => route('videos.stream', $video->id),
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'camera_angle' => $video->camera_angle,
                'duration' => $video->duration,
                'is_master' => $video->is_master,
                'sync_offset' => $video->sync_offset ?? 0,
                'is_synced' => $video->is_synced,
            ]
        ]);
    }
}
