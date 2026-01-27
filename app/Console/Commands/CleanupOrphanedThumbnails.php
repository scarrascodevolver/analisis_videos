<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanupOrphanedThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thumbnails:cleanup-orphaned
                          {--dry-run : Simulate without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup orphaned thumbnail files (thumbnails without associated videos)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No files will be deleted');
        }

        $this->info('Scanning for orphaned thumbnails...');
        $this->newLine();

        // Get all thumbnail paths from database
        $dbThumbnails = Video::whereNotNull('thumbnail_path')
            ->pluck('thumbnail_path')
            ->toArray();

        $this->info("Found " . count($dbThumbnails) . " thumbnails in database");

        $deletedCount = 0;
        $totalSize = 0;
        $disks = ['spaces', 'public'];

        foreach ($disks as $diskName) {
            try {
                $disk = Storage::disk($diskName);

                // Get all thumbnail files from storage
                $thumbnailFiles = $disk->allFiles('thumbnails');

                if (empty($thumbnailFiles)) {
                    $this->line("No thumbnails found on disk: {$diskName}");
                    continue;
                }

                $this->info("Checking {$diskName} disk (" . count($thumbnailFiles) . " files)...");

                foreach ($thumbnailFiles as $filePath) {
                    // Skip if file is in database
                    if (in_array($filePath, $dbThumbnails)) {
                        continue;
                    }

                    // This is an orphaned file
                    try {
                        $fileSize = $disk->size($filePath);
                        $totalSize += $fileSize;

                        if ($dryRun) {
                            $this->line("  Would delete: {$filePath} (" . $this->formatBytes($fileSize) . ")");
                        } else {
                            $disk->delete($filePath);
                            $this->line("  ✓ Deleted: {$filePath} (" . $this->formatBytes($fileSize) . ")");
                        }

                        $deletedCount++;

                    } catch (\Exception $e) {
                        $this->warn("  ⚠️  Error with {$filePath}: " . $e->getMessage());
                    }
                }

            } catch (\Exception $e) {
                $this->error("Error scanning {$diskName} disk: " . $e->getMessage());
            }
        }

        // Summary
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($deletedCount > 0) {
            if ($dryRun) {
                $this->info("Would delete {$deletedCount} orphaned thumbnail(s)");
            } else {
                $this->info("Deleted {$deletedCount} orphaned thumbnail(s)");
            }
            $this->info("Total space freed: " . $this->formatBytes($totalSize));
        } else {
            $this->info("No orphaned thumbnails found. Storage is clean! ✨");
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return 0;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
