<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoDeletionService
{
    /**
     * Delete a single video.
     *
     * Flow:
     * 1. Cancel pending compression jobs (best-effort, tolerates missing jobs table).
     * 2. Delete the DB record inside a transaction (fires VideoObserver for group
     *    cleanup + MySQL CASCADE for comments/assignments/clips/lineups/etc.).
     * 3. Clean up external files after the transaction commits (Spaces, local, Bunny).
     *    Failures are logged but never abort the operation.
     */
    public function delete(Video $video): void
    {
        // Snapshot fields we need after deletion
        $filePath         = $video->file_path ?: null;
        $thumbnailPath    = $video->thumbnail_path ?: null;
        $originalFilePath = ($video->original_file_path && $video->original_file_path !== $video->file_path)
            ? $video->original_file_path
            : null;
        $bunnyVideoId  = $video->bunny_video_id;
        $organization  = $video->relationLoaded('organization') ? $video->organization : null;

        // 1. Cancel pending compression jobs (safe — tolerates missing jobs table)
        $this->cancelCompressionJobs($video);

        // 2. Delete DB record in a transaction (cascade + VideoObserver run here)
        DB::transaction(static function () use ($video): void {
            $video->delete();
        });

        // 3. Clean up external storage (non-critical)
        $this->deleteExternalFiles(
            $video->id,
            $filePath,
            $thumbnailPath,
            $originalFilePath,
            $bunnyVideoId,
            $organization
        );
    }

    /**
     * Delete a collection of videos. Returns the number of successfully deleted records.
     */
    public function deleteMany(Collection $videos): int
    {
        $count = 0;

        foreach ($videos as $video) {
            $this->delete($video);
            $count++;
        }

        return $count;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Try to cancel any queued CompressVideoJob for this video.
     * Catches \Throwable so a missing or unavailable `jobs` table never
     * surfaces as a 500.
     */
    private function cancelCompressionJobs(Video $video): void
    {
        try {
            $deleted = DB::table('jobs')
                ->where('payload', 'like', '%CompressVideoJob%')
                ->where('payload', 'like', "%\"videoId\":{$video->id}%")
                ->delete();

            if ($deleted > 0) {
                Log::info("VideoDeletion: Cancelled {$deleted} pending compression job(s) for video {$video->id}");
            }
        } catch (\Throwable $e) {
            // The `jobs` table may not exist (sync driver, etc.) — non-critical.
            Log::warning("VideoDeletion: Could not cancel compression jobs for video {$video->id}: {$e->getMessage()}");
        }
    }

    /**
     * Delete all external files for a video.
     * Every operation is wrapped with \Throwable so a null path, a network
     * timeout, or a missing adapter never leaks a 500.
     */
    private function deleteExternalFiles(
        int $videoId,
        ?string $filePath,
        ?string $thumbnailPath,
        ?string $originalFilePath,
        ?string $bunnyVideoId,
        $organization
    ): void {
        if ($filePath) {
            $this->deleteFromSpaces($filePath, $videoId, 'main');
            $this->deleteFromLocal($filePath);
        }

        if ($thumbnailPath) {
            $this->deleteFromSpaces($thumbnailPath, $videoId, 'thumbnail');
            $this->deleteFromLocal($thumbnailPath);
        }

        if ($originalFilePath) {
            $this->deleteFromSpaces($originalFilePath, $videoId, 'original');
            $this->deleteFromLocal($originalFilePath);
        }

        if ($bunnyVideoId) {
            try {
                BunnyStreamService::forOrganization($organization)
                    ->deleteVideo($bunnyVideoId);
            } catch (\Throwable $e) {
                Log::warning("VideoDeletion: Bunny delete failed for video {$videoId}", [
                    'bunny_video_id' => $bunnyVideoId,
                    'error'          => $e->getMessage(),
                ]);
            }
        }
    }

    private function deleteFromSpaces(string $path, int $videoId, string $label): void
    {
        try {
            if (Storage::disk('spaces')->exists($path)) {
                Storage::disk('spaces')->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning("VideoDeletion: Spaces {$label} delete failed for video {$videoId}: {$e->getMessage()}");
        }
    }

    private function deleteFromLocal(string $path): void
    {
        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            // Non-critical — local copy may not exist.
        }
    }
}
