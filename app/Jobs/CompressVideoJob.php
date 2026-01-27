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
    public $tries = 1; // Single attempt to avoid blocking queue with retries

    /**
     * The maximum number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 14400; // 4 hours - required for large files (4GB+)

    /**
     * The video ID to compress.
     *
     * @var int
     */
    protected $videoId;

    /**
     * Temporary file paths for cleanup tracking.
     *
     * @var string|null
     */
    protected $tempOriginalPath = null;
    protected $tempCompressedPath = null;
    protected $tempThumbnailPath = null;

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

        // Skip compression in local/development environment
        if (!app()->environment('production')) {
            Log::info("CompressVideoJob: Skipping compression in local environment for video {$video->id}");

            $video->update([
                'processing_status' => 'completed',
                'processing_completed_at' => now(),
                'status' => 'active',
            ]);

            // Delete job from queue
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
            $this->tempOriginalPath = $this->downloadVideoToTemp($video, $disk);

            if (!$this->tempOriginalPath) {
                throw new Exception("Failed to download video for processing");
            }

            Log::info("CompressVideoJob: Downloaded video to {$this->tempOriginalPath}");

            // Create temporary output path
            $this->tempCompressedPath = storage_path('app/temp/compressed_' . basename($this->tempOriginalPath));

            // Compress the video using FFmpeg
            $this->compressVideo($this->tempOriginalPath, $this->tempCompressedPath);

            Log::info("CompressVideoJob: Compression completed, output: {$this->tempCompressedPath}");

            // Re-check if video still exists after compression (user might have deleted it during processing)
            $video->refresh();
            if (!Video::find($this->videoId)) {
                Log::warning("CompressVideoJob: Video {$this->videoId} was deleted during compression. Discarding compressed file.");
                $this->delete(); // Remove job from queue
                return;
            }

            // Upload compressed video to storage
            $newPath = $this->uploadCompressedVideo($video, $this->tempCompressedPath);

            Log::info("CompressVideoJob: Uploaded compressed video to {$newPath}");

            // Generate thumbnail from compressed video
            $thumbnailPath = $this->generateThumbnail($video, $this->tempCompressedPath);
            if ($thumbnailPath) {
                Log::info("CompressVideoJob: Generated thumbnail at {$thumbnailPath}");
            }

            // Update video record with new information
            $originalFileSize = $video->file_size;
            $compressedFileSize = filesize($this->tempCompressedPath);
            $compressionRatio = round(($originalFileSize - $compressedFileSize) / $originalFileSize * 100, 2);

            $video->update([
                'original_file_path' => $video->file_path,
                'original_file_size' => $originalFileSize,
                'file_path' => $newPath,
                'file_size' => $compressedFileSize,
                'compressed_file_size' => $compressedFileSize,
                'compression_ratio' => $compressionRatio,
                'processing_status' => 'completed',
                'processing_completed_at' => now(),
            ]);

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
        } finally {
            // ALWAYS clean up temporary files, regardless of success or failure
            $this->cleanupTempFiles();
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
     * Compress video using FFmpeg with adaptive preset based on file size.
     */
    protected function compressVideo(string $inputPath, string $outputPath): void
    {
        // Get file size in MB
        $fileSizeMB = filesize($inputPath) / 1024 / 1024;

        // Adaptive preset and CRF based on file size
        // Optimized for 2 CPU / 4GB RAM VPS
        // Smaller files: better quality, slower preset
        // Larger files: good quality, faster preset, more aggressive compression
        if ($fileSizeMB < 500) {
            $preset = 'medium';
            $crf = 23;
            $reason = 'Small file (<500MB): using best quality preset';
        } elseif ($fileSizeMB < 2000) {
            $preset = 'fast';
            $crf = 23;
            $reason = 'Medium file (500MB-2GB): using balanced preset';
        } elseif ($fileSizeMB < 4000) {
            $preset = 'veryfast';
            $crf = 22; // Lower CRF for large files to maintain quality
            $reason = 'Large file (2GB-4GB): using speed-optimized preset with enhanced quality (CRF 22)';
        } else {
            $preset = 'veryfast';
            $crf = 24; // More aggressive CRF for very large files (faster processing)
            $reason = 'Very large file (>4GB): using speed-optimized preset with aggressive compression (CRF 24) to reduce processing time';
        }

        Log::info("CompressVideoJob: File size: {$fileSizeMB} MB. {$reason}");

        // FFmpeg command optimized for rugby videos with adaptive settings
        $command = sprintf(
            'ffmpeg -i %s -c:v libx264 -preset %s -crf %d ' .
            '-vf "scale=1920:1080:force_original_aspect_ratio=decrease" ' .
            '-c:a aac -b:a 128k -movflags +faststart -threads 0 -y %s 2>&1',
            escapeshellarg($inputPath),
            $preset,
            $crf,
            escapeshellarg($outputPath)
        );

        Log::info("CompressVideoJob: Executing FFmpeg with preset '{$preset}', CRF {$crf}");

        $startTime = microtime(true);

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        $duration = round(microtime(true) - $startTime, 2);

        if ($returnVar !== 0) {
            $errorOutput = implode("\n", $output);
            Log::error("CompressVideoJob: FFmpeg failed with code {$returnVar} after {$duration}s: {$errorOutput}");
            throw new Exception("FFmpeg compression failed: {$errorOutput}");
        }

        if (!file_exists($outputPath)) {
            throw new Exception("FFmpeg did not produce output file");
        }

        $outputSizeMB = round(filesize($outputPath) / 1024 / 1024, 2);
        Log::info("CompressVideoJob: FFmpeg completed in {$duration}s. Output: {$outputSizeMB}MB (preset: {$preset}, CRF: {$crf})");
    }

    /**
     * Upload compressed video to storage.
     */
    protected function uploadCompressedVideo(Video $video, string $compressedPath): string
    {
        $filename = 'compressed_' . time() . '_' . $video->file_name;

        // Get organization slug for folder structure
        $orgSlug = $video->organization ? $video->organization->slug : 'default';
        $uploadPath = "videos/{$orgSlug}";

        // Production: upload to Spaces with fallback to local
        // Local/Development: use local storage directly (faster, no network delays)
        if (app()->environment('production')) {
            try {
                $path = Storage::disk('spaces')->putFileAs(
                    $uploadPath,
                    new \Illuminate\Http\File($compressedPath),
                    $filename,
                    'public'
                );

                Log::info("CompressVideoJob: Uploaded to DigitalOcean Spaces");
                return $path;

            } catch (Exception $e) {
                Log::warning("CompressVideoJob: Failed to upload to Spaces, using local storage: " . $e->getMessage());

                $path = Storage::disk('public')->putFileAs(
                    $uploadPath,
                    new \Illuminate\Http\File($compressedPath),
                    $filename
                );

                return $path;
            }
        } else {
            // Local environment: use local storage directly
            $path = Storage::disk('public')->putFileAs(
                $uploadPath,
                new \Illuminate\Http\File($compressedPath),
                $filename
            );

            Log::info("CompressVideoJob: Uploaded to local storage (development environment)");
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
            $this->tempThumbnailPath = $tempDir . '/' . $thumbnailFilename;

            // Extract frame at 2 seconds (or 1 second for short videos)
            $command = sprintf(
                'ffmpeg -i %s -ss 00:00:02 -vframes 1 -vf "scale=640:-1" -q:v 2 -y %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($this->tempThumbnailPath)
            );

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);

            if ($returnVar !== 0 || !file_exists($this->tempThumbnailPath)) {
                // Try at 1 second if 2 seconds failed (video might be shorter)
                $command = sprintf(
                    'ffmpeg -i %s -ss 00:00:01 -vframes 1 -vf "scale=640:-1" -q:v 2 -y %s 2>&1',
                    escapeshellarg($videoPath),
                    escapeshellarg($this->tempThumbnailPath)
                );
                exec($command, $output, $returnVar);
            }

            if (!file_exists($this->tempThumbnailPath)) {
                Log::warning("CompressVideoJob: Failed to generate thumbnail");
                return null;
            }

            // Get organization slug for path
            $orgSlug = $video->organization ? $video->organization->slug : 'default';
            $storagePath = "thumbnails/{$orgSlug}/{$thumbnailFilename}";

            // Upload to storage
            try {
                Storage::disk('spaces')->put($storagePath, file_get_contents($this->tempThumbnailPath), 'public');
                Log::info("CompressVideoJob: Thumbnail uploaded to Spaces");
            } catch (Exception $e) {
                // Fallback to local storage
                Storage::disk('public')->put($storagePath, file_get_contents($this->tempThumbnailPath));
                Log::info("CompressVideoJob: Thumbnail uploaded to local storage");
            }

            // Cleanup temp file (also tracked for finally block)
            @unlink($this->tempThumbnailPath);

            return $storagePath;

        } catch (Exception $e) {
            Log::warning("CompressVideoJob: Thumbnail generation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean up temporary files.
     * Called from finally block to ensure cleanup regardless of job outcome.
     */
    protected function cleanupTempFiles(): void
    {
        $cleaned = [];

        if ($this->tempOriginalPath && file_exists($this->tempOriginalPath)) {
            @unlink($this->tempOriginalPath);
            $cleaned[] = basename($this->tempOriginalPath);
        }

        if ($this->tempCompressedPath && file_exists($this->tempCompressedPath)) {
            @unlink($this->tempCompressedPath);
            $cleaned[] = basename($this->tempCompressedPath);
        }

        if ($this->tempThumbnailPath && file_exists($this->tempThumbnailPath)) {
            @unlink($this->tempThumbnailPath);
            $cleaned[] = basename($this->tempThumbnailPath);
        }

        if (!empty($cleaned)) {
            Log::info("CompressVideoJob: Cleaned up temp files: " . implode(', ', $cleaned));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        // Ensure temp files are cleaned up on final failure
        $this->cleanupTempFiles();

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
