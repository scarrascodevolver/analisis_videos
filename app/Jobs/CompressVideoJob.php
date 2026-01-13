<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class CompressVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 7200; // 2 hours

    /**
     * The video ID to compress.
     *
     * @var int
     */
    protected $videoId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $videoId)
    {
        $this->videoId = $videoId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $video = Video::find($this->videoId);

        if (!$video) {
            Log::warning("CompressVideoJob: Video {$this->videoId} not found (probably deleted). Skipping job.");

            // Delete this job from queue to prevent retries
            $this->delete();
            return;
        }

        Log::info("CompressVideoJob: Starting compression for video {$video->id}: {$video->title}");

        try {
            // Determine which disk is being used
            $disk = $this->getDiskForVideo($video);

            // Verify file exists before processing
            if (!Storage::disk($disk)->exists($video->file_path)) {
                Log::warning("CompressVideoJob: Video file not found on disk '{$disk}': {$video->file_path}. Video may have been deleted. Skipping job.");

                // Mark as failed instead of retrying
                $video->update([
                    'processing_status' => 'failed',
                    'processing_completed_at' => now(),
                ]);

                // Delete this job from queue
                $this->delete();
                return;
            }

            // Update status to processing
            $video->update([
                'processing_status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // Download video to temporary location for processing
            $tempOriginalPath = $this->downloadVideoToTemp($video, $disk);

            if (!$tempOriginalPath) {
                throw new Exception("Failed to download video for processing");
            }

            Log::info("CompressVideoJob: Downloaded video to {$tempOriginalPath}");

            // Create temporary output path
            $tempCompressedPath = storage_path('app/temp/compressed_' . basename($tempOriginalPath));

            // Compress the video using FFmpeg
            $this->compressVideo($tempOriginalPath, $tempCompressedPath);

            Log::info("CompressVideoJob: Compression completed, output: {$tempCompressedPath}");

            // Upload compressed video to storage
            $newPath = $this->uploadCompressedVideo($video, $tempCompressedPath);

            Log::info("CompressVideoJob: Uploaded compressed video to {$newPath}");

            // Generate thumbnail from compressed video
            $thumbnailPath = $this->generateThumbnail($video, $tempCompressedPath);
            if ($thumbnailPath) {
                Log::info("CompressVideoJob: Generated thumbnail at {$thumbnailPath}");
            }

            // Update video record with new information
            $originalFileSize = $video->file_size;
            $compressedFileSize = filesize($tempCompressedPath);
            $compressionRatio = round(($originalFileSize - $compressedFileSize) / $originalFileSize * 100, 2);

            $updateData = [
                'original_file_path' => $video->file_path,
                'original_file_size' => $originalFileSize,
                'file_path' => $newPath,
                'file_size' => $compressedFileSize,
                'compressed_file_size' => $compressedFileSize,
                'compression_ratio' => $compressionRatio,
                'processing_status' => 'completed',
                'processing_completed_at' => now(),
            ];

            if ($thumbnailPath) {
                $updateData['thumbnail_path'] = $thumbnailPath;
            }

            $video->update($updateData);

            // Clean up temporary files
            @unlink($tempOriginalPath);
            @unlink($tempCompressedPath);

            // Delete original file from storage if it's different from compressed path
            if ($video->original_file_path && $video->original_file_path !== $newPath) {
                try {
                    Storage::disk($disk)->delete($video->original_file_path);
                    Log::info("CompressVideoJob: Deleted original file from storage");
                } catch (Exception $e) {
                    Log::warning("CompressVideoJob: Failed to delete original file: " . $e->getMessage());
                }
            }

            Log::info("CompressVideoJob: Completed successfully for video {$video->id}. Compression: {$compressionRatio}%");

        } catch (Exception $e) {
            Log::error("CompressVideoJob: Failed for video {$video->id}: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            $video->update([
                'processing_status' => 'failed',
                'processing_completed_at' => now(),
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Determine which disk the video is stored on.
     */
    protected function getDiskForVideo(Video $video): string
    {
        // Check if file exists on Spaces
        if (Storage::disk('spaces')->exists($video->file_path)) {
            return 'spaces';
        }

        // Check if file exists on public disk
        if (Storage::disk('public')->exists($video->file_path)) {
            return 'public';
        }

        // Default to local/private
        return 'local';
    }

    /**
     * Download video to temporary location for processing.
     */
    protected function downloadVideoToTemp(Video $video, string $disk): ?string
    {
        try {
            // Create temp directory if it doesn't exist
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir . '/' . time() . '_' . basename($video->file_path);

            // Get file contents from storage
            $fileContents = Storage::disk($disk)->get($video->file_path);

            if (!$fileContents) {
                return null;
            }

            // Write to temp file
            file_put_contents($tempPath, $fileContents);

            return $tempPath;

        } catch (Exception $e) {
            Log::error("CompressVideoJob: Failed to download video: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compress video using FFmpeg.
     */
    protected function compressVideo(string $inputPath, string $outputPath): void
    {
        // FFmpeg command optimized for rugby videos
        $command = sprintf(
            'ffmpeg -i %s -c:v libx264 -preset medium -crf 23 ' .
            '-vf "scale=1920:1080:force_original_aspect_ratio=decrease" ' .
            '-c:a aac -b:a 128k -movflags +faststart -threads 0 -y %s 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        Log::info("CompressVideoJob: Executing FFmpeg command");

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorOutput = implode("\n", $output);
            Log::error("CompressVideoJob: FFmpeg failed with code {$returnVar}: {$errorOutput}");
            throw new Exception("FFmpeg compression failed: {$errorOutput}");
        }

        if (!file_exists($outputPath)) {
            throw new Exception("FFmpeg did not produce output file");
        }

        Log::info("CompressVideoJob: FFmpeg completed successfully");
    }

    /**
     * Upload compressed video to storage.
     */
    protected function uploadCompressedVideo(Video $video, string $compressedPath): string
    {
        $filename = 'compressed_' . time() . '_' . $video->file_name;

        // Try to upload to Spaces first, fallback to local
        try {
            $path = Storage::disk('spaces')->putFileAs(
                'videos',
                new \Illuminate\Http\File($compressedPath),
                $filename,
                'public'
            );

            Log::info("CompressVideoJob: Uploaded to DigitalOcean Spaces");
            return $path;

        } catch (Exception $e) {
            Log::warning("CompressVideoJob: Failed to upload to Spaces, using local storage: " . $e->getMessage());

            $path = Storage::disk('public')->putFileAs(
                'videos',
                new \Illuminate\Http\File($compressedPath),
                $filename
            );

            return $path;
        }
    }

    /**
     * Generate thumbnail from video using FFmpeg.
     */
    protected function generateThumbnail(Video $video, string $videoPath): ?string
    {
        try {
            $tempDir = storage_path('app/temp');
            $thumbnailFilename = pathinfo($video->file_name, PATHINFO_FILENAME) . '_thumb.jpg';
            $tempThumbnailPath = $tempDir . '/' . $thumbnailFilename;

            // Extract frame at 2 seconds (or 1 second for short videos)
            $command = sprintf(
                'ffmpeg -i %s -ss 00:00:02 -vframes 1 -vf "scale=640:-1" -q:v 2 -y %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($tempThumbnailPath)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($tempThumbnailPath)) {
                // Try at 1 second if 2 seconds failed (video might be shorter)
                $command = sprintf(
                    'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf "scale=640:-1" -q:v 2 -y %s 2>&1',
                    escapeshellarg($videoPath),
                    escapeshellarg($tempThumbnailPath)
                );
                exec($command, $output, $returnVar);
            }

            if (!file_exists($tempThumbnailPath)) {
                Log::warning("CompressVideoJob: Failed to generate thumbnail");
                return null;
            }

            // Get organization slug for path
            $orgSlug = $video->organization ? $video->organization->slug : 'default';
            $storagePath = "thumbnails/{$orgSlug}/{$thumbnailFilename}";

            // Upload to storage
            try {
                Storage::disk('spaces')->put($storagePath, file_get_contents($tempThumbnailPath), 'public');
                Log::info("CompressVideoJob: Thumbnail uploaded to Spaces");
            } catch (Exception $e) {
                // Fallback to local storage
                Storage::disk('public')->put($storagePath, file_get_contents($tempThumbnailPath));
                Log::info("CompressVideoJob: Thumbnail uploaded to local storage");
            }

            // Cleanup temp file
            @unlink($tempThumbnailPath);

            return $storagePath;

        } catch (Exception $e) {
            Log::warning("CompressVideoJob: Thumbnail generation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        $video = Video::find($this->videoId);

        if ($video) {
            $video->update([
                'processing_status' => 'failed',
                'processing_completed_at' => now(),
            ]);
        }

        Log::error("CompressVideoJob: Job failed permanently for video {$this->videoId}: " . $exception->getMessage());
    }
}
