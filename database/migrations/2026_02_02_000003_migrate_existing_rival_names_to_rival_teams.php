<?php

use App\Models\Organization;
use App\Models\RivalTeam;
use App\Models\Video;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create RivalTeam records for each unique rival_team_name per organization
        $organizations = Organization::all();

        foreach ($organizations as $org) {
            // Get unique rival names for this organization
            $uniqueRivals = Video::withoutGlobalScope('organization')
                ->where('organization_id', $org->id)
                ->whereNotNull('rival_team_name')
                ->where('rival_team_name', '!=', '')
                ->select('rival_team_name')
                ->distinct()
                ->pluck('rival_team_name');

            Log::info("Migrating rival names for organization {$org->name}", [
                'organization_id' => $org->id,
                'unique_rivals' => $uniqueRivals->count(),
            ]);

            foreach ($uniqueRivals as $rivalName) {
                // Create or get RivalTeam record
                $rival = RivalTeam::firstOrCreate([
                    'organization_id' => $org->id,
                    'name' => $rivalName,
                ]);

                // Update all videos with this rival name
                $updatedCount = Video::withoutGlobalScope('organization')
                    ->where('organization_id', $org->id)
                    ->where('rival_team_name', $rivalName)
                    ->whereNull('rival_team_id') // Only update if not already set
                    ->update(['rival_team_id' => $rival->id]);

                Log::info("Created rival '{$rivalName}' and updated {$updatedCount} videos", [
                    'organization_id' => $org->id,
                    'rival_team_id' => $rival->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all rival_team_id back to null (keep rival_team_name intact)
        DB::table('videos')->update(['rival_team_id' => null]);

        // Delete all RivalTeam records
        DB::table('rival_teams')->truncate();
    }
};
