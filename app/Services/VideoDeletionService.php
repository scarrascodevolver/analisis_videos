<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoDeletionService
{
    /**
     * Delete remote/local assets (best effort) and remove the DB record.
     *
     * @return array{warnings: array<int, string>}
     */
    public function deleteVideo(Video $video): array
    {
        $warnings = [];

        $warnings = array_merge($warnings, $this->deleteStorageAssets($video));
        $warnings = array_merge($warnings, $this->deleteBunnyAsset($video));

        // The DB delete is the source of truth. If this fails, caller must know.
        $video->delete();

        return ['warnings' => $warnings];
    }

    /**
     * @return array<int, string>
     */
    private function deleteStorageAssets(Video $video): array
    {
        $warnings = [];

        $paths = array_values(array_filter(array_unique([
            $video->file_path,
            $video->thumbnail_path,
            ($video->original_file_path && $video->original_file_path !== $video->file_path)
                ? $video->original_file_path
                : null,
        ])));

        foreach ($paths as $path) {
            if ($this->isVirtualProviderPath($path)) {
                continue;
            }

            $warnings = array_merge($warnings, $this->deleteFromDisk('spaces', $path, $video->id));
            $warnings = array_merge($warnings, $this->deleteFromDisk('public', $path, $video->id));
        }

        return $warnings;
    }

    /**
     * @return array<int, string>
     */
    private function deleteFromDisk(string $disk, string $path, int $videoId): array
    {
        try {
            if ($disk === 'spaces') {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } else {
                Storage::disk($disk)->delete($path);
            }

            return [];
        } catch (\Throwable $e) {
            $message = "Video {$videoId}: failed deleting '{$path}' from disk '{$disk}': {$e->getMessage()}";
            Log::warning($message);

            return [$message];
        }
    }

    /**
     * @return array<int, string>
     */
    private function deleteBunnyAsset(Video $video): array
    {
        if (! $video->bunny_video_id) {
            return [];
        }

        try {
            BunnyStreamService::forOrganization($video->organization)
                ->deleteVideo($video->bunny_video_id);

            return [];
        } catch (\Throwable $e) {
            $message = "Video {$video->id}: Bunny delete failed for {$video->bunny_video_id}: {$e->getMessage()}";
            Log::warning($message, [
                'video_id' => $video->id,
                'bunny_video_id' => $video->bunny_video_id,
            ]);

            return [$message];
        }
    }

    private function isVirtualProviderPath(string $path): bool
    {
        return str_starts_with($path, 'bunny:')
            || str_starts_with($path, 'cloudflare:')
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://');
    }
}
