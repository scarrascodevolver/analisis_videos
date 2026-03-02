<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RegenerateThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:regenerate-thumbnails
                          {--video-id= : Specific video ID to process}
                          {--missing-only : Only process videos without thumbnails}
                          {--dry-run : Simulate without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate thumbnails for videos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $videoId = $this->option('video-id');
        $missingOnly = $this->option('missing-only');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Build query
        $query = Video::query();

        if ($videoId) {
            $query->where('id', $videoId);
        }

        if ($missingOnly) {
            $query->whereNull('thumbnail_path');
        }

        // Only process completed videos
        $query->where('processing_status', 'completed');

        $videos = $query->get();

        if ($videos->isEmpty()) {
            $this->warn('No videos found matching criteria.');

            return 0;
        }

        $this->info("Found {$videos->count()} video(s) to process.\n");

        $successCount = 0;
        $skipCount = 0;
        $errorCount = 0;

        foreach ($videos as $video) {
            $this->line("Processing Video #{$video->id}: {$video->title}");

            // Determine disk
            $disk = $this->getDiskForVideo($video);

            // Check if video file exists
            if (! Storage::disk($disk)->exists($video->file_path)) {
                $this->error("  ❌ Video file not found on disk '{$disk}': {$video->file_path}");
                $errorCount++;

                continue;
            }

            if ($dryRun) {
                $this->info("  ✓ Would generate thumbnail for video {$video->id}");
                $successCount++;

                continue;
            }

            try {
                // Download video to temp
                $tempPath = $this->downloadVideoToTemp($video, $disk);

                if (! $tempPath) {
                    $this->error('  ❌ Failed to download video to temp');
                    $errorCount++;

                    continue;
                }

                // Generate thumbnail
                $thumbnailPath = $this->generateThumbnail($video, $tempPath);

                // Cleanup temp file
                @unlink($tempPath);

                if ($thumbnailPath) {
                    // Update database
                    $video->update(['thumbnail_path' => $thumbnailPath]);
                    $this->info("  ✅ Thumbnail generated: {$thumbnailPath}");
                    $successCount++;
                } else {
                    $this->warn('  ⚠️  Failed to generate thumbnail');
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $this->error('  ❌ Error: '.$e->getMessage());
                Log::error("RegenerateThumbnails failed for video {$video->id}: ".$e->getMessage());
                $errorCount++;
            }

            $this->newLine();
        }

        // Summary
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Summary:');
        $this->info("  ✅ Success: {$successCount}");
        if ($skipCount > 0) {
            $this->info("  ⏭️  Skipped: {$skipCount}");
        }
        if ($errorCount > 0) {
            $this->warn("  ❌ Errors:  {$errorCount}");
        }
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Determine which disk the video is stored on.
     */
    protected function getDiskForVideo(Video $video): string
    {
        if (Storage::disk('public')->exists($video->file_path)) {
            return 'public';
        }

        return 'local';
    }

    /**
     * Download video to temporary location.
     */
    protected function downloadVideoToTemp(Video $video, string $disk): ?string
    {
        try {
            $tempDir = storage_path('app/temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir.'/thumb_'.time().'_'.basename($video->file_path);

            $this->line('  📥 Downloading video...');

            $fileContents = Storage::disk($disk)->get($video->file_path);

            if (! $fileContents) {
                return null;
            }

            file_put_contents($tempPath, $fileContents);

            return $tempPath;

        } catch (\Exception $e) {
            Log::error("Failed to download video {$video->id}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Generate thumbnail from video using FFmpeg.
     */
    protected function generateThumbnail(Video $video, string $videoPath): ?string
    {
        try {
            $tempDir = storage_path('app/temp');
            $thumbnailFilename = pathinfo($video->file_name, PATHINFO_FILENAME).'_thumb.jpg';
            $tempThumbnailPath = $tempDir.'/'.$thumbnailFilename;

            $this->line('  🎨 Generating thumbnail...');

            // Extract frame at 2 seconds
            $command = sprintf(
                'ffmpeg -i %s -ss 00:00:02 -vframes 1 -vf "scale=640:-1" -q:v 2 -y %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($tempThumbnailPath)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || ! file_exists($tempThumbnailPath)) {
                // Try at 1 second if 2 seconds failed
                $command = sprintf(
                    'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf "scale=640:-1" -q:v 2 -y %s 2>&1',
                    escapeshellarg($videoPath),
                    escapeshellarg($tempThumbnailPath)
                );
                exec($command, $output, $returnVar);
            }

            if (! file_exists($tempThumbnailPath)) {
                return null;
            }

            // Get organization slug for path
            $orgSlug = $video->organization ? $video->organization->slug : 'default';
            $storagePath = "thumbnails/{$orgSlug}/{$thumbnailFilename}";

            $this->line('  📤 Uploading thumbnail...');

            Storage::disk('public')->put($storagePath, file_get_contents($tempThumbnailPath));

            // Cleanup temp thumbnail
            @unlink($tempThumbnailPath);

            return $storagePath;

        } catch (\Exception $e) {
            Log::warning("Thumbnail generation failed for video {$video->id}: ".$e->getMessage());

            return null;
        }
    }
}
