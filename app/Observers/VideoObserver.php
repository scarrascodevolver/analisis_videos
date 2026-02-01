<?php

namespace App\Observers;

use App\Models\Video;
use Illuminate\Support\Facades\Log;

class VideoObserver
{
    /**
     * Handle the Video "deleting" event.
     * Clean up multi-camera group associations BEFORE video is deleted.
     *
     * RULES:
     * - If video is MASTER in a group → Dissolve entire group (delete group + detach all videos)
     * - If video is SLAVE in a group → Only remove it from the group (keep group with remaining videos)
     */
    public function deleting(Video $video): void
    {
        Log::info("VideoObserver: Cleaning up video {$video->id} ('{$video->title}') from multi-camera groups");

        // Get all groups this video belongs to
        $groups = $video->videoGroups()->get();

        if ($groups->isEmpty()) {
            Log::info("Video {$video->id} is not part of any multi-camera group - no cleanup needed");

            return;
        }

        foreach ($groups as $group) {
            // Check if this video is the MASTER in this group
            $isMaster = $video->videoGroups()
                ->where('video_groups.id', $group->id)
                ->wherePivot('is_master', true)
                ->exists();

            if ($isMaster) {
                // Video is MASTER → Dissolve entire group
                Log::info("Video {$video->id} is MASTER in group {$group->id} ('{$group->name}'). Dissolving entire group.");

                // Get all videos in group for logging
                $groupVideos = $group->videos()->pluck('id')->toArray();
                Log::info("Group {$group->id} has ".count($groupVideos).' videos: '.implode(', ', $groupVideos));

                // Detach all videos from group (including master)
                $group->videos()->detach();
                Log::info("Detached all videos from group {$group->id}");

                // Delete the group itself
                $group->delete();
                Log::info("Group {$group->id} dissolved successfully (master video deleted)");
            } else {
                // Video is SLAVE → Just remove it from this group
                Log::info("Video {$video->id} is SLAVE in group {$group->id}. Removing from group (keeping group intact).");

                // Detach only this slave video from the group
                $video->videoGroups()->detach($group->id);

                // Count remaining videos in group
                $remainingCount = $group->videos()->count();
                Log::info("Video {$video->id} removed from group {$group->id}. Remaining videos: {$remainingCount}");
            }
        }

        Log::info("VideoObserver: Cleanup completed for video {$video->id}");
    }
}
