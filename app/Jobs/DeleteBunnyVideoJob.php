<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\BunnyStreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteBunnyVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public readonly string $bunnyVideoId,
        public readonly int $organizationId,
    ) {}

    public function handle(): void
    {
        $org = Organization::find($this->organizationId);

        if (! $org) {
            Log::warning("DeleteBunnyVideoJob: organization {$this->organizationId} not found, skipping.");
            return;
        }

        try {
            BunnyStreamService::forOrganization($org)->deleteVideo($this->bunnyVideoId);
        } catch (\Exception $e) {
            Log::warning("DeleteBunnyVideoJob: failed to delete {$this->bunnyVideoId}: " . $e->getMessage());
            throw $e; // allow retry
        }
    }
}
