<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill existing organizations that have no bunny_library_id
     * with the global credentials stored in the environment config.
     *
     * This ensures the organization "Los Troncos" (library 601622) keeps
     * working after the multi-library refactor.
     */
    public function up(): void
    {
        $libraryId = config('filesystems.bunny_stream.library_id');
        $apiKey = config('filesystems.bunny_stream.api_key');
        $cdnHostname = config('filesystems.bunny_stream.cdn_hostname');

        // Only backfill when global credentials are actually configured.
        // In CI / fresh installs these values may be empty, so we skip.
        if (! $libraryId || ! $apiKey) {
            return;
        }

        DB::table('organizations')
            ->whereNull('bunny_library_id')
            ->update([
                'bunny_library_id' => $libraryId,
                'bunny_api_key' => $apiKey,
                'bunny_cdn_hostname' => $cdnHostname,
            ]);
    }

    public function down(): void
    {
        // Intentionally left empty: we do not want to wipe credentials on rollback.
    }
};
