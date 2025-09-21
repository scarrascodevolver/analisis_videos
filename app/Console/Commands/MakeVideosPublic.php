<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Video;

class MakeVideosPublic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:make-public {--dry-run : Run without making changes}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Make all existing videos in DigitalOcean Spaces public';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get all videos that have file paths in Spaces
        $videos = Video::whereNotNull('file_path')
                      ->where('file_path', 'like', 'videos/%')
                      ->orderBy('id')
                      ->get();

        if ($videos->isEmpty()) {
            $this->warn('No videos found in DigitalOcean Spaces');
            return;
        }

        $this->info("Found {$videos->count()} videos to process");

        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($videos as $video) {
            try {
                $filePath = $video->file_path;

                // Check if file exists in Spaces
                if (!Storage::disk('spaces')->exists($filePath)) {
                    $this->newLine();
                    $this->warn("⚠️  Video ID {$video->id}: File not found in Spaces: {$filePath}");
                    $errorCount++;
                    $bar->advance();
                    continue;
                }

                if (!$isDryRun) {
                    // Make the file public
                    Storage::disk('spaces')->setVisibility($filePath, 'public');
                }

                $this->newLine();
                $this->info("✅ Video ID {$video->id}: " . ($isDryRun ? 'Would make public' : 'Made public') . " - {$filePath}");
                $successCount++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Video ID {$video->id}: Failed - {$e->getMessage()}");
                $errors[] = "Video ID {$video->id}: {$e->getMessage()}";
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        if ($isDryRun) {
            $this->info("🔍 DRY RUN SUMMARY:");
            $this->info("   - Would process: {$successCount} videos");
            $this->info("   - Errors/Warnings: {$errorCount}");
        } else {
            $this->info("✅ OPERATION COMPLETED:");
            $this->info("   - Successfully processed: {$successCount} videos");
            $this->info("   - Errors: {$errorCount}");
        }

        if (!empty($errors)) {
            $this->newLine();
            $this->error("Errors encountered:");
            foreach ($errors as $error) {
                $this->error("   - {$error}");
            }
        }

        if (!$isDryRun && $successCount > 0) {
            $this->newLine();
            $this->info("🎉 All videos are now public in DigitalOcean Spaces!");
            $this->info("You can test CDN URLs like:");
            $this->info("https://analisis-videos-storage.sfo3.cdn.digitaloceanspaces.com/videos/filename.mp4");
        }

        return $errorCount > 0 ? 1 : 0;
    }
}