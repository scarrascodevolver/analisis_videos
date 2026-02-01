<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MultiCameraController extends Controller
{
    /**
     * Get all angles for a video group
     * Supports multiple groups - returns specific group or first group
     */
    public function getGroupAngles(Request $request, Video $video)
    {
        $groupId = $request->input('group_id');

        // Check if video is in any group
        if (! $video->isPartOfGroup()) {
            return response()->json([
                'success' => false,
                'message' => 'This video is not part of a multi-camera group',
            ], 404);
        }

        // Get all groups this video belongs to
        $allGroups = $video->getGroups();

        // If groupId specified, use that group
        if ($groupId) {
            $group = VideoGroup::find($groupId);
            if (! $group || ! $video->isInGroup($groupId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video is not part of the specified group',
                ], 404);
            }
        } else {
            // Use first group
            $group = $allGroups->first();
        }

        $master = $group->getMasterVideo();
        $slaves = $group->getSlaveVideos();

        return response()->json([
            'success' => true,
            'current_group_id' => $group->id,
            'groups' => $allGroups->map(function ($g) {
                return [
                    'id' => $g->id,
                    'name' => $g->name ?? 'Grupo #'.$g->id,
                    'video_count' => $g->videos()->count(),
                ];
            }),
            'master' => [
                'id' => $master->id,
                'title' => $master->title,
                'camera_angle' => $master->pivot->camera_angle ?? 'Master',
                'is_synced' => true,
                'sync_offset' => 0,
            ],
            'angles' => $slaves->map(function ($slave) {
                return [
                    'id' => $slave->id,
                    'title' => $slave->title,
                    'camera_angle' => $slave->pivot->camera_angle,
                    'is_synced' => $slave->pivot->is_synced,
                    'sync_offset' => $slave->pivot->sync_offset,
                    'sync_reference_event' => $slave->pivot->sync_reference_event,
                    'file_size' => $slave->file_size,
                    'duration' => $slave->duration,
                ];
            }),
        ]);
    }

    /**
     * Search videos to associate as angles
     * UPDATED: No longer excludes videos already in groups (multi-group support)
     */
    public function searchVideos(Request $request)
    {
        $query = $request->input('query', '');
        $organizationId = auth()->user()->currentOrganization()->id;

        // NOTE: We no longer filter by exclude_group_id because videos can be in multiple groups
        // The user may want to add the same video to different groups

        $videos = Video::where('organization_id', $organizationId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('analyzed_team_name', 'LIKE', "%{$query}%")
                    ->orWhere('rival_team_name', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('match_date')
            ->limit(10)
            ->get(['id', 'title', 'match_date', 'file_size', 'duration', 'thumbnail_path']);

        return response()->json([
            'success' => true,
            'videos' => $videos,
        ]);
    }

    /**
     * Associate a video as an angle to a master video
     * UPDATED: Supports multiple groups, no longer blocks videos already in groups
     */
    public function associateAngle(Request $request, Video $video)
    {
        $request->validate([
            'slave_video_id' => 'required|exists:videos,id',
            'camera_angle' => 'required|string|max:100',
            'group_id' => 'nullable|exists:video_groups,id', // Optional: specific group to add to
        ]);

        $masterVideo = $video;
        $slaveVideo = Video::findOrFail($request->slave_video_id);
        $groupId = $request->input('group_id');

        Log::info('=== ASSOCIATE DEBUG START (NEW SYSTEM) ===');
        Log::info("Master Video ID: {$masterVideo->id}");
        Log::info("Slave Video ID: {$slaveVideo->id}");
        Log::info('Target Group ID: '.($groupId ?? 'auto'));
        Log::info("Camera Angle: {$request->camera_angle}");

        // Get or create group
        if ($groupId) {
            $group = VideoGroup::find($groupId);
            if (! $group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found',
                ], 404);
            }

            // Verify master is in this group
            if (! $masterVideo->isInGroup($groupId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Master video is not in the specified group',
                ], 400);
            }
        } else {
            // Get master's first group or create new one
            $group = $masterVideo->videoGroups()->first();

            if (! $group) {
                $group = VideoGroup::create([
                    'name' => null,
                    'organization_id' => $masterVideo->organization_id,
                ]);

                $masterAngle = $request->input('master_angle', 'Master / Tribuna Central');
                $group->videos()->attach($masterVideo->id, [
                    'is_master' => true,
                    'camera_angle' => $masterAngle,
                    'is_synced' => true,
                    'sync_offset' => 0,
                ]);

                Log::info("Created new group {$group->id} for master video {$masterVideo->id}");
            }
        }

        // Check if slave is already in THIS group
        if ($slaveVideo->isInGroup($group->id)) {
            Log::warning("Slave video {$slaveVideo->id} is already in group {$group->id}");

            return response()->json([
                'success' => false,
                'message' => 'This video is already in this group',
            ], 400);
        }

        // Check maximum angles limit (3 slaves max for performance)
        $currentSlaveCount = $group->getSlaveVideos()->count();
        if ($currentSlaveCount >= 3) {
            Log::warning("Group {$group->id} already has {$currentSlaveCount} slaves (limit: 3)");

            return response()->json([
                'success' => false,
                'message' => 'Máximo 3 ángulos permitidos por razones de rendimiento. Con más de 3 videos sincronizados, el rendimiento se degrada significativamente.',
            ], 400);
        }

        // Associate slave to master in the group
        Log::info("Calling associateToMaster with group_id: {$group->id}, camera_angle: {$request->camera_angle}");
        $success = $slaveVideo->associateToMaster($masterVideo, $request->camera_angle, $group->id);

        if ($success) {
            Log::info("Video {$slaveVideo->id} associated to master {$masterVideo->id} in group {$group->id}");

            // Get all angles in this group
            $slaves = $group->getSlaveVideos();
            $angles = $slaves->map(function ($slave) {
                return [
                    'id' => $slave->id,
                    'title' => $slave->title,
                    'camera_angle' => $slave->pivot->camera_angle,
                    'is_synced' => $slave->pivot->is_synced,
                    'sync_offset' => $slave->pivot->sync_offset,
                    'sync_reference_event' => $slave->pivot->sync_reference_event,
                    'file_size' => $slave->file_size,
                    'duration' => $slave->duration,
                ];
            });

            Log::info('Returning '.$angles->count().' angles in response');
            Log::info('=== ASSOCIATE DEBUG END ===');

            return response()->json([
                'success' => true,
                'message' => "Angle '{$request->camera_angle}' associated successfully",
                'group_id' => $group->id,
                'slave_video' => [
                    'id' => $slaveVideo->id,
                    'title' => $slaveVideo->title,
                    'camera_angle' => $request->camera_angle,
                    'is_synced' => false,
                ],
                'angles' => $angles,
            ]);
        }

        Log::info('=== ASSOCIATE DEBUG END ===');

        return response()->json([
            'success' => false,
            'message' => 'Failed to associate angle',
        ], 500);
    }

    /**
     * Remove an angle from a group
     * UPDATED: Supports removing from specific group
     */
    public function removeAngle(Request $request, Video $video)
    {
        $groupId = $request->input('group_id');

        if ($groupId) {
            // Check if video is slave in this specific group
            if (! $video->isSlave($groupId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only slave videos can be removed from a group',
                ], 400);
            }

            $video->removeFromGroup($groupId);
            Log::info("Video {$video->id} removed from group {$groupId}");
        } else {
            // Remove from all groups (backward compatibility)
            if (! $video->isSlave()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only slave videos can be removed from a group',
                ], 400);
            }

            $video->removeFromGroup();
            Log::info("Video {$video->id} removed from all groups");
        }

        return response()->json([
            'success' => true,
            'message' => 'Angle removed from group successfully',
        ]);
    }

    /**
     * Sync a slave video with the master
     * UPDATED: Supports syncing in specific group
     */
    public function syncAngle(Request $request, Video $video)
    {
        $request->validate([
            'sync_offset' => 'required|numeric|between:-300,300', // Max ±5 minutes
            'reference_event' => 'nullable|string|max:255',
            'group_id' => 'nullable|exists:video_groups,id',
        ]);

        $groupId = $request->input('group_id');

        // Check if video is slave in specified group or any group
        if ($groupId) {
            if (! $video->isSlave($groupId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only slave videos can be synced in this group',
                ], 400);
            }
        } else {
            if (! $video->isSlave()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only slave videos can be synced',
                ], 400);
            }
        }

        $success = $video->syncWithMaster(
            $request->sync_offset,
            $request->input('reference_event'),
            $groupId
        );

        if ($success) {
            Log::info("Video {$video->id} synced with offset: {$request->sync_offset}s in group ".($groupId ?? 'default'));

            // Get updated pivot data if group specified
            if ($groupId) {
                $pivot = $video->videoGroups()->where('video_groups.id', $groupId)->first()->pivot;
                $cameraAngle = $pivot->camera_angle;
                $syncOffset = $pivot->sync_offset;
                $syncReferenceEvent = $pivot->sync_reference_event;
            } else {
                $cameraAngle = $video->camera_angle;
                $syncOffset = $video->sync_offset;
                $syncReferenceEvent = $video->sync_reference_event;
            }

            return response()->json([
                'success' => true,
                'message' => 'Video synced successfully',
                'video' => [
                    'id' => $video->id,
                    'camera_angle' => $cameraAngle,
                    'is_synced' => true,
                    'sync_offset' => $syncOffset,
                    'sync_reference_event' => $syncReferenceEvent,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to sync video',
        ], 500);
    }

    /**
     * Reset sync for a slave video
     * UPDATED: Supports resetting sync in specific group
     */
    public function resetSync(Request $request, Video $video)
    {
        $groupId = $request->input('group_id');

        if ($groupId) {
            if (! $video->isSlave($groupId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only slave videos can be reset in this group',
                ], 400);
            }

            // Reset sync in specific group
            $video->videoGroups()->updateExistingPivot($groupId, [
                'sync_offset' => null,
                'is_synced' => false,
                'sync_reference_event' => null,
            ]);

            $pivot = $video->videoGroups()->where('video_groups.id', $groupId)->first()->pivot;
            $cameraAngle = $pivot->camera_angle;

            Log::info("Video {$video->id} sync reset in group {$groupId}");
        } else {
            if (! $video->isSlave()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only slave videos can be reset',
                ], 400);
            }

            // Reset sync in all groups
            foreach ($video->videoGroups as $group) {
                $video->videoGroups()->updateExistingPivot($group->id, [
                    'sync_offset' => null,
                    'is_synced' => false,
                    'sync_reference_event' => null,
                ]);
            }

            // Get camera angle from first group
            $firstGroup = $video->videoGroups->first();
            $cameraAngle = $firstGroup ? $firstGroup->pivot->camera_angle : null;

            Log::info("Video {$video->id} sync reset in all groups");
        }

        return response()->json([
            'success' => true,
            'message' => 'Synchronization reset successfully',
            'video' => [
                'id' => $video->id,
                'camera_angle' => $cameraAngle,
                'is_synced' => false,
                'sync_offset' => null,
                'sync_reference_event' => null,
            ],
        ]);
    }

    /**
     * Get video stream URL for multi-camera player
     */
    public function getStreamUrl(Video $video)
    {
        // Get data from first group if video is in a group
        $firstGroup = $video->videoGroups->first();
        $pivot = $firstGroup ? $firstGroup->pivot : null;

        return response()->json([
            'success' => true,
            'stream_url' => route('videos.stream', $video->id),
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'camera_angle' => $pivot->camera_angle ?? null,
                'duration' => $video->duration,
                'is_master' => $pivot->is_master ?? false,
                'sync_offset' => $pivot->sync_offset ?? 0,
                'is_synced' => $pivot->is_synced ?? false,
            ],
        ]);
    }
}
