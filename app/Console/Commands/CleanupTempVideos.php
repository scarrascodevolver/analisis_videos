<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupTempVideos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'videos:cleanup-temp
                            {--hours=24 : Delete files older than this many hours}
                            {--dry-run : Run without deleting files}
                            {--force : Delete all temp files regardless of age}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Clean up orphan temporary video files from storage/app/temp';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $forceAll = $this->option('force');
        $maxHours = (int) $this->option('hours');

        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            $this->info('Temp directory does not exist. Nothing to clean.');

            return 0;
        }

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No files will be deleted');
        }

        if ($forceAll) {
            $this->warn('FORCE MODE - Will delete ALL temp files regardless of age');
            $maxHours = 0;
        }

        $this->info("Scanning temp directory: {$tempDir}");
        $this->info("Looking for files older than {$maxHours} hours...");
        $this->newLine();

        $files = glob($tempDir.'/*');

        if (empty($files)) {
            $this->info('No files found in temp directory.');

            return 0;
        }

        $this->info('Found '.count($files).' files in temp directory');

        $cutoffTime = $forceAll ? PHP_INT_MAX : time() - ($maxHours * 3600);
        $deletedCount = 0;
        $skippedCount = 0;
        $totalSize = 0;
        $freedSize = 0;
        $errors = [];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $filename = basename($file);
            $fileSize = filesize($file);
            $totalSize += $fileSize;
            $fileModTime = filemtime($file);
            $fileAge = round((time() - $fileModTime) / 3600, 1);

            // Check if file is old enough to delete
            if ($fileModTime > $cutoffTime && ! $forceAll) {
                $this->line("  SKIP: {$filename} ({$this->formatBytes($fileSize)}) - {$fileAge}h old");
                $skippedCount++;

                continue;
            }

            // File is old enough, delete it
            try {
                if (! $isDryRun) {
                    unlink($file);
                }

                $action = $isDryRun ? 'WOULD DELETE' : 'DELETED';
                $this->info("  {$action}: {$filename} ({$this->formatBytes($fileSize)}) - {$fileAge}h old");
                $deletedCount++;
                $freedSize += $fileSize;

            } catch (\Exception $e) {
                $this->error("  ERROR: {$filename} - {$e->getMessage()}");
                $errors[] = $filename;
            }
        }

        $this->newLine();
        $this->info('='.str_repeat('=', 50));

        if ($isDryRun) {
            $this->info('DRY RUN SUMMARY:');
            $this->info("  Files that would be deleted: {$deletedCount}");
            $this->info("  Space that would be freed: {$this->formatBytes($freedSize)}");
            $this->info("  Files that would be kept: {$skippedCount}");
        } else {
            $this->info('CLEANUP COMPLETED:');
            $this->info("  Files deleted: {$deletedCount}");
            $this->info("  Space freed: {$this->formatBytes($freedSize)}");
            $this->info("  Files kept (too recent): {$skippedCount}");

            if ($deletedCount > 0) {
                Log::info("CleanupTempVideos: Deleted {$deletedCount} files, freed {$this->formatBytes($freedSize)}");
            }
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->error('Errors encountered with files:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return empty($errors) ? 0 : 1;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }
}
